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
    $data[$tableIndex]['th'] = trim($nodeList->getElementsByTagName('th')->item(0)->nodeValue);
    foreach ($nodeList->getElementsByTagName('tr') as $rowIndex => $row) {
      $desc = $row->getElementsByTagName('td')->item(0);
      if (!$desc) {
        continue;
      }
      $data[$tableIndex][$rowIndex] = [];
      $data[$tableIndex][$rowIndex]['td'] = trim($desc->nodeValue);
      $data[$tableIndex][$rowIndex]['data'] = [];
      foreach ($row->getElementsByTagName('td') as $dataIndex => $dataNode) {
        if ($dataIndex === 0) continue;
        $data[$tableIndex][$rowIndex]['data'][] = trim(preg_replace('/[^(\x20-\x7F)]*/', '', $dataNode->nodeValue));
      }
    }
}
ddd($data);
die;
// collect data
$data = array();
foreach ($xpath->query('//tbody[@id="index:srednjiKursLista:tbody_element"]/tr') as $node) {
    $rowData = array();
    foreach ($xpath->query('td', $node) as $cell) {
        $rowData[] = $cell->nodeValue;
    }

    $data[] = array_combine($headerNames, $rowData);
}

// $rows = $tables->item(1)->getElementsByTagName('tr');

// foreach ($rows as $row) {
        // $cols = $row->getElementsByTagName('td');
        // echo $cols[2];
// }
