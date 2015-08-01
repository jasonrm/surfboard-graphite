<?php

function denormalizeSourceData($data)
{
    $denormalized = [
        'name' => strtolower($data['name']),
        'fields' => [],
    ];

    for ($i = 0; $i < count($data['measurements'][0]['values']); $i++) {
        $chunk = [];
        foreach ($data['measurements'] as $measurement) {
            $valueParts = explode(' ', $measurement['values'][$i]);
            if (isset($valueParts[1])) {
                $chunk[$measurement['name']] = $valueParts[0];
                $chunk[$measurement['name'] . '_unit'] = $valueParts[1];
            } else {
                $chunk[$measurement['name']] = $valueParts[0];
            }
        }
        $denormalized['fields'][] = $chunk;
    }

    return $denormalized;
}

function denormalizeMeasurement($name, $serialNumber, $measurement)
{
    $denormalizedMeasurements = [];
    $channelId = $measurement['channel_id'];
    foreach ($measurement as $field => $value) {
        if ($field == 'channel_id') {
            continue;
        }
        if (stripos($field, '_unit') !== false) {
            continue;
        }
        $tags = [
            'type' => $field,
            'channel_id' => $channelId,
            'serial_number' => $serialNumber,
        ];
        if (isset($measurement[$field . '_unit'])) {
            $tags['unit'] = $measurement[$field . '_unit'];
        }
        if (is_numeric($value)) {
            $value = floatval($value);
        } else {
            continue;
        }
        $denormalizedMeasurements[] = ['name' => $name, 'fields' => ['value' => $value], 'tags' => $tags];
    }
    return $denormalizedMeasurements;
}

function parseSignalData($html)
{
    $dom = new DOMDocument();
    @$dom->loadHTML($html);
    $xpath = new DomXPath($dom);
    $data = [];
    foreach ($xpath->query('//center/table') as $tableIndex => $nodeList) {
        $data[$tableIndex] = [];
        $nameNode = $nodeList->getElementsByTagName('th')->item(0);
        $groupName = \Doctrine\Common\Inflector\Inflector::tableize($nameNode->nodeValue);
        $groupName = preg_replace('/[^a-zA-Z0-9]/', '_', $groupName);
        $groupName = preg_replace('/_+/', '_', $groupName);
        $groupName = trim($groupName, '_');
        $data[$tableIndex]['name'] = $groupName;
        $data[$tableIndex]['measurements'] = [];
        foreach ($nodeList->getElementsByTagName('tr') as $rowIndex => $row) {
            $desc = $row->getElementsByTagName('td')->item(0);
            if (!$desc) {
                continue;
            }
            $name = trim($desc->firstChild->nodeValue);
            if (strlen($name) > 64) {
                continue;
            }
            $name = \Doctrine\Common\Inflector\Inflector::tableize($name);
            $name = str_replace(' ', '_', $name);
            $name = str_replace('i_d', 'id', $name);
            $rowData = [];
            $rowData['name'] = $name;
            $rowData['values'] = [];
            foreach ($row->getElementsByTagName('td') as $dataIndex => $dataNode) {
                if ($dataIndex === 0) {
                    continue;
                }
                $value = trim(preg_replace('/[^(\x20-\x7F)]*/', '', $dataNode->nodeValue));
                if (strlen($value) > 64) {
                    continue;
                }
                $rowData['values'][] = $value;
            }
            $data[$tableIndex]['measurements'][] = $rowData;
        }
    }
    return $data;
}
