<?php
$formats = '';

if (isset($_GET['verb']))
{
	if ($_GET['verb'] == 'ListMetadataFormats')
	{
		$formats = './ListMetadataFormats.xml';	
	}
	else if ($_GET['verb'] == 'ListRecords')
	{
		if (isset($_GET['metadataPrefix']))
		{
			$formats = './ore/ListRecords_with_set.xml';
		}
	}
	else if ($_GET['verb'] == 'GetRecord')
	{
        if ($_GET['identifier'] == 'oai:archive.bora.wijiti.net:10049/74')
        {
            $formats = './ore/GetRecord.xml';
        }
        else if ($_GET['identifier'] == 'oai:archive.bora.wijiti.net:10049/76')
        {
            $formats = './ore/GetRecord2.xml';
        }
        
	}
}

$xml = simplexml_load_file($formats);
header('Content-Type: text/xml');
print $xml->asXML();
?>
