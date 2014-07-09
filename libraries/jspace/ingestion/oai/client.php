<?php
/**
 * @package     JSpace
 * @subpackage  Ingestion
 *
 * @copyright   Copyright (C) 2014 KnowledgeARC Ltd. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */
 
defined('_JEXEC') or die;

/**
 * Provides a client for connecting to an OAI-aware provider.
 *
 * @package     JSpace
 * @subpackage  Ingestion
 */
class JSpaceIngestionOAIClient
{
	private $url;

	/**
	 * Instantiates an instance of the JSpaceIngestionOAIClient class.
	 *
	 * @param  JUri  $url  The url of the OAI provider to connect to.
	 */
	public function __construct($url)
	{
		if (!(string)$url)
		{
			throw new InvalidArgumentException('URL cannot be empty.');
		}
		
		$this->url = $url;
	}

	/**
	 * Pings the OAI provider.
	 *
	 * @return  bool  True if the server can be identified, false otherwise.
	 */
	public function ping()
	{
		try 
		{
			$identify = $this->identify();
			
			if (!isset($identify->error))
			{
				return true;
			}
			
		}
		catch(Exception $e)
		{
			// fail quietly.
		}
		
		return false;
	}
	
	/**
	 * Requests identification information about the OAI provider.
	 *
	 * @return  simple_xml_element  Information about the OAI provider.
	 */
	public function identify()
	{
		$this->url->setVar('verb', 'Identify');
		
		$element = @simplexml_load_file((string)$this->url);
		
		if ($element === false)
		{
			throw new RuntimeException('Error while parsing the document.');
		}
		else 
		{
			return $element;
		}
	}
	
	/**
	 * Searches the OAI provider for the specified metadata prefix.
	 *
	 * @param   string  $metadataPrefix  The metadata format to search for.
	 *
	 * @return  bool    True if the metdata format can be found, false otherwise.
	 */
	public function hasMetadataFormat($metadataPrefix)
	{
		$found = false;
		
		$this->url->setQuery(array('verb'=>'ListMetadataFormats'));
		
		$element = @simplexml_load_file((string)$this->url);
		
		if ($element === false)
		{
			throw new RuntimeException('Error while parsing the document.');
		}
		else 
		{
			if (isset($element->ListMetadataFormats->metadataFormat))
			{
				$metadataFormats = $element->ListMetadataFormats->metadataFormat;
				
				$i = 0;
				while ($i < count($metadataFormats) && !$found)
				{		
					if ($metadataFormats[$i]->metadataPrefix == $metadataPrefix)
					{
						$found = true;
					}
					
					$i++;
				}
			}
		}
		
		return $found;
	}
	
	/**
	 * Searches the OAI provider for the specified metadata prefixes.
	 *
	 * @param   array                 $metadataPrefixes  The prefixes to search for.
	 *
	 * @return  simple_xml_element[]  An array of available metadata prefixes.
	 */
	public function hasMetadataFormats($metadataPrefixes)
	{
		$found = array();
		
		$this->url->setQuery(array('verb'=>'ListMetadataFormats'));
		
		$element = @simplexml_load_file((string)$this->url);
		
		if ($element === false)
		{
			throw new RuntimeException('Error while parsing the document.');
		}
		else 
		{
			if (current($element->ListMetadataFormats) !== null)
			{
				$metadataFormats = current($element->ListMetadataFormats);
				
				$i = 0;
				while ($i < count($metadataFormats))
				{
					if (array_search((string)$metadataFormats[$i]->metadataPrefix, $metadataPrefixes) !== false)
					{
						$found[(string)$metadataFormats[$i]->metadataPrefix] = $metadataFormats[$i];
					}
					
					$i++;
				}
				
				$ordered = array();
				
				foreach($metadataPrefixes as $key)
				{
					if(array_key_exists($key, $found))
					{
						$ordered[$key] = $found[$key];
						unset($found[$key]);
					}
				}
				
				$found = $ordered;
			}
		}
		
		return $found;
	}
	
	/**
	 * Gets a list of OAI records.
	 *
	 * @param   array      $params  A list of parameters to add to the request.
	 *
	 * @return  XmlReader  An XML reader object.
	 *
	 * @throws  Exception  When a url cannot be read.
	 */
	public function listRecords($params)
	{
		$params = array_merge(array('verb'=>'ListRecords'), $params);
		$this->url->setQuery($params);
		
		return $this->_getXMLReader();
	}
	
	/**
	 * Gets a single OAI record based on the specified identifier.
	 *
	 * @param   int        $identifier      The identifier of the record to retrieve.
	 * @param   string     $metadataPrefix  The metadata format of the item to retrieve.
	 *
	 * @return  XmlReader  An XML reader object.
	 */
	public function getRecord($identifier, $metadataPrefix)
	{
		$params = array('verb'=>'GetRecord');
		$params['identifier'] = $identifier;
		$params['metadataPrefix'] = $metadataPrefix;
		
		$this->url->setQuery($params);
		
		return $this->_getXMLReader();
	}
	
	public function _getXMLReader()
	{
		$reader = new XMLReader;
		$tries = 0;
		
		while (!$reader->open((string)$this->url) && $tries < 3)
		{
			$tries++;
			sleep(10);
		}
		
		if ($tries >= 3)
		{
			throw new Exception(__METHOD__.' Tried to access '.$uri.' but was unable to open it after '.$tries.' tries. Exiting...', 404);
		}
		
		return $reader;
	}
}