<?php
$formats = '';

if (isset($_GET['verb']))
{
    if ($_GET['verb'] == 'Identify')
    {
        $formats = './Identify.xml'; 
    }
	else if ($_GET['verb'] == 'ListMetadataFormats')
	{
		$formats = './ListMetadataFormats.xml';	
	}
	else if ($_GET['verb'] == 'ListRecords')
	{
		if (isset($_GET['metadataPrefix']) && $_GET['metadataPrefix'] == 'qdc')
		{
		    if (isset($_GET['set']))
            {
                $formats = './qdc/ListRecords_with_set.xml';
            }
            else
            {
                $formats = './qdc/ListRecords.xml';
            }
		}
		else if (isset($_GET['resumptionToken']) && $_GET['resumptionToken'] == 'MToxMDB8Mjp8Mzp8NDp8NTpxZGM=')
		{
			$formats = './qdc/ListRecords2.xml';
		}
		else if (isset($_GET['metadataPrefix']) && $_GET['metadataPrefix'] == 'oai_dc')
		{
			if (isset($_GET['set']))
            {
                $formats = './oai_dc/ListRecords_with_set.xml';
            }
            else
            {
                $formats = './oai_dc/ListRecords.xml';
            }
		}
		else if (isset($_GET['resumptionToken']) && $_GET['resumptionToken'] == 'MToxMDB8Mjp8Mzp8NDp8NTpvYWlfZGM=')
		{
			$formats = './oai_dc/ListRecords2.xml';
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