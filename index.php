<?php
require __DIR__ . '/vendor/autoload.php';

# Use the Curl extension to query Google and get back a page of results
$url = "http://192.168.100.1/cmSignalData.htm";
$ch = curl_init();
$timeout = 5;
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
$html = curl_exec($ch);
curl_close($ch);

# Create a DOM parser object
$dom = new DOMDocument();

# Parse the HTML from Google.
# The @ before the method call suppresses any warnings that
# loadHTML might throw because of invalid HTML in the page.
@$dom->loadHTML($html);

$xpath = new DomXPath($dom);

$data = [];
foreach ($xpath->query('//center/table') as $tableIndex => $nodeList) {
    $data[$tableIndex] = [];
    $nameNode = $nodeList->getElementsByTagName('th')->item(0);
    $data[$tableIndex]['name'] = trim($nameNode->nodeValue);
    $data[$tableIndex]['measurements'] = [];
    foreach ($nodeList->getElementsByTagName('tr') as $rowIndex => $row) {
      $desc = $row->getElementsByTagName('td')->item(0);
      if (!$desc) continue;
      $name = trim($desc->firstChild->nodeValue);
      if (strlen($name) > 64) continue;
      $name = \Doctrine\Common\Inflector\Inflector::tableize($name);
      $name = str_replace(' ', '_', $name);
      $name = str_replace('i_d', 'id', $name);
      $rowData = [];
      $rowData['name'] = $name;
      $rowData['values'] = [];
      foreach ($row->getElementsByTagName('td') as $dataIndex => $dataNode) {
        if ($dataIndex === 0) continue;
        $value = trim(preg_replace('/[^(\x20-\x7F)]*/', '', $dataNode->nodeValue));
        if (strlen($value) > 64) continue;
        $rowData['values'][] = $value;
      }
      $data[$tableIndex]['measurements'][] = $rowData;
    }
}

function denormalize($data) {
  $denormalized = [];

  for ($i=0; $i < count($data['measurements'][0]['values']); $i++) {
    $chunk = [];
    foreach ($data['measurements'] as $measurement) {
      $chunk[$measurement['name']] = $measurement['values'][$i];
    }
    $denormalized[] = $chunk;
  }

  return $denormalized;
}
