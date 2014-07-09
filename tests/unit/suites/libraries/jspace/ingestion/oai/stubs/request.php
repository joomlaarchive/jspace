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
		if (isset($_GET['metadataPrefix']) && $_GET['metadataPrefix'] == 'qdc')
		{
			$formats = './ListRecords.xml';
		}
		else if (isset($_GET['resumptionToken']) && $_GET['resumptionToken'] == 'MToxMDB8Mjp8Mzp8NDp8NTpxZGM=')
		{
			$formats = './ListRecords2.xml';
		}
		else if (isset($_GET['resumptionToken']) && $_GET['resumptionToken'] == 'MToyMDB8Mjp8Mzp8NDp8NTpxZGM=')
		{
			$formats = './ListRecords3.xml';
		}
		else if (isset($_GET['resumptionToken']) && $_GET['resumptionToken'] == 'MTozMDB8Mjp8Mzp8NDp8NTpxZGM=')
		{
			$formats = './ListRecords4.xml';
		}
		else if (isset($_GET['metadataPrefix']) && $_GET['metadataPrefix'] == 'ore')
		{
			$formats = './ListRecords_ore.xml';
		}
		else if (isset($_GET['resumptionToken']) && $_GET['resumptionToken'] == 'MToxMDB8Mjp8Mzp8NDp8NTpvcmU=')
		{
			$formats = './ListRecords2_ore.xml';
		}
		else if (isset($_GET['resumptionToken']) && $_GET['resumptionToken'] == 'MToyMDB8Mjp8Mzp8NDp8NTpvcmU=')
		{
			$formats = './ListRecords3_ore.xml';
		}
		else if (isset($_GET['resumptionToken']) && $_GET['resumptionToken'] == 'MTozMDB8Mjp8Mzp8NDp8NTpvcmU=')
		{
			$formats = './ListRecords4_ore.xml';
		}
		else if (isset($_GET['metadataPrefix']) && $_GET['metadataPrefix'] == 'oai_dc')
		{
			$formats = './ListRecords_oai_dc.xml';
		}
		else if (isset($_GET['resumptionToken']) && $_GET['resumptionToken'] == 'MToxMDB8Mjp8Mzp8NDp8NTpvYWlfZGM=')
		{
			$formats = './ListRecords2_oai_dc.xml';
		}
		else if (isset($_GET['resumptionToken']) && $_GET['resumptionToken'] == 'MToyMDB8Mjp8Mzp8NDp8NTpvYWlfZGM=')
		{
			$formats = './ListRecords3_oai_dc.xml';
		}
		else if (isset($_GET['resumptionToken']) && $_GET['resumptionToken'] == 'MTozMDB8Mjp8Mzp8NDp8NTpvYWlfZGM=')
		{
			$formats = './ListRecords4_oai_dc.xml';
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
?>

