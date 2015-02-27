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
		if (isset($_GET['metadataPrefix']) && ($_GET['metadataPrefix'] == 'qdc' || $_GET['metadataPrefix'] == 'oai_dc'))
		{
			$formats = './ListRecordsBadResumptionToken.xml';
		}
		else if ($_GET['resumptionToken'] == 'MToxMDB8Mjp8Mzp8NDp8NTpxZBAD')
		{
			$formats = './ListRecordsBadResumptionToken2.xml';
		}
	}
}

$xml = simplexml_load_file($formats);
header('Content-Type: text/xml');
print $xml->asXML();
?>
