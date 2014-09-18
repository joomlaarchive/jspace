<?php
$formats = '';

if (isset($_GET['verb']))
{
    if ($_GET['verb'] == 'ListRecords')
    {
        if (isset($_GET['metadataPrefix']) && $_GET['metadataPrefix'] == 'qdc')
        {
            $formats = './qdc/ListRecords_with_set2.xml';
        }
    }
}

$xml = simplexml_load_file($formats);
header('Content-Type: text/xml');
print $xml->asXML();
?>