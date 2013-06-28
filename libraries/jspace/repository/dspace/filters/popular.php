<?php
/**
 * A filter class.
 * 
 * @package		JSpace
 * @subpackage	Repository
 * @copyright	Copyright (C) 2012 Wijiti Pty Ltd. All rights reserved.
 * @license     This file is part of the JSpace library for Joomla!.

   The JSpace library for Joomla! is free software: you can redistribute it 
   and/or modify it under the terms of the GNU General Public License as 
   published by the Free Software Foundation, either version 3 of the License, 
   or (at your option) any later version.

   The JSpace library for Joomla! is distributed in the hope that it will be 
   useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.

   You should have received a copy of the GNU General Public License
   along with the JSpace library for Joomla!.  If not, see 
   <http://www.gnu.org/licenses/>.

 * Contributors
 * Please feel free to add your name and email (optional) here if you have 
 * contributed any source code changes.
 * Name							Email
 * Michał Kocztorz				<michalkocztorz@wijiti.com> 
 * 
 */
 
defined('JPATH_PLATFORM') or die;
jimport('jspace.factory');

/**
 * @package     JSpace
 * @subpackage  Repository
 */
class JSpaceRepositoryDspaceFiltersPopular extends JSpaceRepositoryFilter
{
	/**
	 * ToDo: Filtering is not done yet (filterBy option)
	 * ToDo: can sort for multiple columns?
	 * 
	 * (non-PHPdoc)
	 * @see JSpaceRepositoryFilter::getItems()
	 */
	public function getItems() {
		$vars = array(
			'facet'				=> 'true',
			'rows'				=> 0,
			'facet.mincount'	=> 1,
			'start'				=> $this->getLimitstart(),
			'facet.limit'		=> $this->getLimit(),
			'q'					=> 'type:2',
			'facet.field'		=> 'id',
			'fq'				=> '-isBot:true'
		);
		
		try {
			$response = $this->getRepository()->restCallJSON('statistics', $vars);
			if( isset($response->facet_counts->facet_fields->id) ) {
				$array = $response->facet_counts->facet_fields->id;
				$ids = array();
				
				for ($i = 0; $i < count($array); $i+=2) {
					$ids[$array[$i]] = $array[$i+1];
				}
			}
			else {
				return array();
			}
			
			foreach( $ids as $id => $cnt ) {
				try {
					$items[ $id ] = $this->getRepository()->getItem( $id );
				}
				catch( Exception $ex ) {
					//item not found, keep loading other
					JSpaceRepositoryError::raiseError($this, JText::_('COM_JSPACE_REPOSITORY_FILTER_ITEM_NOT_FOUND') . $id);
				}
			}
		} catch (Exception $e) {
			JSpaceRepositoryError::raiseError($this, JText::_('COM_JSPACE_REPOSITORY_CANT_FILTER_ITEMS'));
			JSpaceRepositoryError::raiseError($this, JText::_($e->getMessage()));
			return array();
		}
		
		return $items;
		
		$cparams = JComponentHelper::getComponent("com_jspace", true);
		$url = new JURI($cparams->get("rest_url").'/statistics.json');
		
		try {
			$client = new JRestClient($url->toString(), 'get');
			$client->execute();
				
			if (JArrayHelper::getValue($client->getResponseInfo(), "http_code") == 200) {
				$response = json_decode($client->getResponseBody());
		
				if (isset($response->facet_counts->facet_fields->id)) {
					$array = $response->facet_counts->facet_fields->id;
					$ids = array();
						
					for ($i = 0; $i < count($array); $i+=2) {
						$ids[$array[$i]] = $array[$i+1];
					}
						
					$docs = self::_discoverItems($ids, $params);
		
					foreach ($ids as $key=>$value) {
						$found = false;
		
						reset($docs);
						while (($doc = current($docs)) && !$found) {
							if ($key == $doc->{'search.resourceid'}) {
								$doc->count = $value;
								$items[] = $doc;
								$found = true;
							}
								
							next($docs);
						}
					}
				}
			}
		} catch (Exception $e) {
			JLog::add("modJSpaceItemsPopularHelper: ".$e->getMessage(), JLog::ERROR, 'module');
			return array();
		}
		
		return $items;
	}
}




