<?php
/**
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

jimport('jspace.oai.resumptiontoken.listidentifiers');

/**
 * 
 * Subclass of JSpaceOAIRequestListIdentifiers because these two verbs are almost identical.
 * 
 * @author Michał Kocztorz
 * @package     JSpace
 * @subpackage  OAI
 */
class JSpaceOAIRequestListSets extends JSpaceOAIRequest
{
	/**
	 * Required HTTP OAI-PMH request arguments.
	 * "required, the argument must be included with the request (the verb argument is always required, as described in HTTP Request Format)"
	 *
	 * @var array
	 */
	protected $_required = array('verb');
	
	/**
	 * Optional HTTP OAI-PMH request arguments. When present it should be the only argument apart from verb.
	 * "exclusive, the argument may be included with request, but must be the only argument (in addition to the verb argument)"
	 *
	 * @var array
	 */
	protected $_exclusive = array('resumptionToken');
	
	
	/**
	 * (non-PHPdoc)
	 * @see JSpaceOAIRequest::_load()
	 */
	protected function _load() {
		$resumptionToken = $this->_input->getString('resumptionToken', null);
		if( !is_null( $resumptionToken ) ) {
			//not expected
			throw new JSpaceOAIExceptionBadResumptionToken();
		}
		$this->_setResponseBody();
	}
	
	/**
	 * (non-PHPdoc)
	 * @see JSpaceOAIRequest::_setResponseBody()
	 */
	public function _setResponseBody() {
		$ListSets = $this->_responseXml->addChild('ListSets');
		
		$rootCategory = JSpaceFactory::getRepository()->getCategory();
		$this->_addSet($ListSets, $rootCategory, array());
		$this->_listChildCategories($ListSets, $rootCategory, array());
	}
	
	/**
	 * 
	 * @param SimpleXMLElement $parent
	 * @param JSpaceRepositoryCategory $category
	 * @param array $idPath //passed for speed, could have been calculated
	 */
	protected function _addSet( SimpleXMLElement $parent, JSpaceRepositoryCategory $category, $idPath ) {
		$set = $parent->addChild('set');
		$setSpecValue = implode(':', $idPath) . ( (count($idPath) > 0)? ':' : '' ) . $category->getId();
		$setSpec = $set->addChild('setSpec', $setSpecValue);
		
		$setNameValue = str_replace( "&", "&amp;", $category->getName() ); //prevent xml error
		$setName = $set->addChild('setName', $setNameValue);
	}
	
	/**
	 * 
	 * @param SimpleXMLElement $parent
	 * @param JSpaceRepositoryCategory $category
	 * @param array $idPath //passed for speed, could have been calculated
	 */
	protected function _listChildCategories( SimpleXMLElement $parent, JSpaceRepositoryCategory $category, $idPath) {
		$idPath[] = $category->getId();
		$children = $category->getChildren();
		foreach( $children as $subCategory ) {
			$this->_addSet($parent, $subCategory, $idPath);
			$this->_listChildCategories($parent, $subCategory, $idPath);
		}
	}
}




