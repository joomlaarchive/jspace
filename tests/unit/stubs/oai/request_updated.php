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
    else if ($_GET['verb'] == 'GetRecord')
    {
        if (isset($_GET['metadataPrefix']) && isset($_GET['identifier']))
        {
            $formats = 'http://archive.bora.knowledgearc.net/oai/request?verb=GetRecord&identifier='.$_GET['identifier'].'&metadataPrefix='.$_GET['metadataPrefix'];
        }
    }
}

$xml = simplexml_load_file($formats);
header('Content-Type: text/xml');
print $xml->asXML();