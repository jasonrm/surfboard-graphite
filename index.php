<?php
require __DIR__ . '/vendor/autoload.php';

require 'functions.php';

function getPage($url)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    $html = curl_exec($ch);
    curl_close($ch);
    return $html;
}

// Use serial number for ID
$addressDataHTML = getPage("http://192.168.100.1/cmAddressData.htm");
$dom = new DOMDocument();
@$dom->loadHTML($addressDataHTML);
$xpath = new DomXPath($dom);
$serialNumber = $xpath->query('//table/tbody/tr/td')->item(1)->nodeValue;

while (true) {
    $signalDataHTML = getPage("http://192.168.100.1/cmSignalData.htm");
    $data = parseSignalData($signalDataHTML);

    $client = new \crodas\InfluxPHP\Client("10.59.52.10", 8086, "root", "root");
    $db = $client->createDatabase("graphite");

    foreach ($data as $measurementSet) {
        $preparedMeasurements = denormalizeSourceData($measurementSet);
        foreach ($preparedMeasurements['fields'] as $preparedMeasurement) {
            $name = $preparedMeasurements['name'];
            $preparedForInflux = denormalizeMeasurement($name, $serialNumber, $preparedMeasurement);
            echo json_encode($preparedForInflux) . "\n";
            $db->insert($name, $preparedForInflux);
        }
    }
    sleep(30);
}
