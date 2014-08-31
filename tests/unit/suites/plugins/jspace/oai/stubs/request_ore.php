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
		if (isset($_GET['metadataPrefix']) && $_GET['metadataPrefix'] == 'ore')
		{
			$formats = './ListRecords_ore.xml';
		}
		else if ($_GET['resumptionToken'] == 'MToxMDB8Mjp8Mzp8NDp8NTpvcmU=')
		{
			$formats = './ListRecords2_ore.xml';
		}
		else if ($_GET['resumptionToken'] == 'MToyMDB8Mjp8Mzp8NDp8NTpvcmU=')
		{
			$formats = './ListRecords3_ore.xml';
		}
		else if ($_GET['resumptionToken'] == 'MTozMDB8Mjp8Mzp8NDp8NTpvcmU=')
		{
			$formats = './ListRecords4_ore.xml';
		}
	}
}

$xml = simplexml_load_file($formats);
header('Content-Type: text/xml');
print $xml->asXML();
?>
