<?php
defined('JPATH_BASE') or die;

class PlgJSpaceOAI extends JPlugin
{
	/**
	 *
	 */
	public function __construct($subject, $config = array())
	{	
		parent::__construct($subject, $config);
		$this->loadLanguage();
	}
	
	public function onJSpaceExecuteCliCommand($action, $options)
	{
		if ($action == 'harvest')
		{
			foreach ($this->_getOAICategories() as $category)
			{
				$params = new JRegistry();
				$params->loadString($category->params);
				
				$this->_harvest($category->url, $category->metadataFormat, $params);
			}
		}
	}
	
	/**
	 * Get a list of categories which are populated via an OAI-PMH endpoint.
	 *
	 * @return mixed A list of OAI-enabled categories or null if there is a problem fetching the categories.
	 */
	private function _getOAICategories()
	{
		$database = JFactory::getDbo();
		
		$query = $database->getQuery(true);
		
		$select = array(
			$database->qn('a.url'),
			$database->qn('a.metadataFormat'),
			$database->qn('a.harvestParams', 'params'));
		$query
			->select($select)
			->from($database->qn('#__joai_harvests', 'a'))
			->innerjoin($database->qn('#__categories', 'b').' ON '.$database->qn('a.catid').'='.$database->qn('b.id'))
			->where($database->qn('b.published').'='.$database->q('1'));
		
		$database->setQuery($query);
		
		return $database->loadObjectList();
	}
	
	/**
	 * Harvest a list of records via an OAI-PMH-enabled endpoint.
	 *
	 * @param string $url The url to harvest.
	 * @param string $metadataFormat The format to harvest the metadata.
	 * @param array $params An array of additional parameters associated with the harvest.
	 */
	private function _harvest($url, $metadataFormat, $params)
	{
		$resumptionToken = null;
	
		$uri = JUri::getInstance($url);
		$uri->setVar('verb', 'ListRecords');
		
		do
		{
			if ($resumptionToken)
			{
				$uri->delVar('metadataPrefix');
				$uri->setVar('resumptionToken', $resumptionToken);
				
				// take a break to avoid any timeout issues.
				sleep($this->params->get('follow_on', 30));
			}
			else 
			{
				$uri->setVar('metadataPrefix', $metadataFormat);
			}
		
			$reader = new XMLReader;
			
			$reader->open((string)$uri);

			$doc = new DOMDocument;			
			
			while ($reader->read())
			{
				if ($reader->nodeType == XMLReader::ELEMENT)
				{
					switch ($reader->name)
					{
						case 'error':
							$node = simplexml_import_dom($doc->importNode($reader->expand(), true));
							throw new Exception((string)$node);
							break;
					
						case 'resumptionToken':
							$node = simplexml_import_dom($doc->importNode($reader->expand(), true));
							$resumptionToken = (string)$node;
							break;
					
						case 'record':
							$node = simplexml_import_dom($doc->importNode($reader->expand(), true));
							
							foreach ($node->metadata->children($metadataFormat, true) as $key=>$value)
							{
								$array = array();

								foreach ($value->getNamespaces(true) as $keyns=>$valuens)
								{
									foreach ($value->children($keyns, true) as $key2=>$value2)
									{
										if (array_key_exists($keyns.":".$key2, $array))
										{
											if (!is_array($array[$keyns.":".$key2]))
											{
												$array[$keyns.":".$key2] = array($array[$keyns.":".$key2]);
											}
											
											$array[$keyns.":".$key2][] = (string)$value2;
										}
										else
										{
											$array[$keyns.":".$key2] = (string)$value2;
										}
									}					
								}
								
								$registry = new JRegistry();
								$registry->loadArray($array);

								$item = JSpaceFactory::getCrosswalk($registry, array('name'=>$metadataFormat))->walk();
								
								$this->_presave($item);
							}
							
							break;
							
						default:
							break;
					}
				}
			}
		}
		while ($resumptionToken);
		
		$this->_save();
	}
	
	private function _presave($item)
	{
		
	}
	
	private function _save()
	{
		
	}
}