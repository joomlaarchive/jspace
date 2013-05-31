<?php
/**
 *
 * @author		$LastChangedBy: michalkocztorz $
 * @package		JSpace
 * @copyright	Copyright (C) 2011 Wijiti Pty Ltd. All rights reserved.
 * @license     This file is part of the JSpace component for Joomla!.

 The JSpace component for Joomla! is free software: you can redistribute it
 and/or modify it under the terms of the GNU General Public License as
 published by the Free Software Foundation, either version 3 of the License,
 or (at your option) any later version.

 The JSpace component for Joomla! is distributed in the hope that it will be
 useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with the JSpace component for Joomla!.  If not, see
 <http://www.gnu.org/licenses/>.

 * Contributors
 * Please feel free to add your name and email (optional) here if you have
 * contributed any source code changes.
 * Name							Email
 * Michał Kocztorz				<michalkocztorz@wijiti.com>
 *
 */

defined('JPATH_PLATFORM') or die;

jimport('joomla.database.table');

/**
 * Items table
 *
 * @package     JSpace
 * @subpackage  Table
 * @since       11.1
 */
class JSpaceTableMetadata extends JTable
{
	const METADATA_SUBJECT			= 'subject';
	const METADATA_INSTITUTION		= 'institution';
	const METADATA_MATERIAL_TYPE	= 'material_type';
	const METADATA_AUTHOR			= 'author';
	const METADATA_TYPE				= 'type';
	const METADATA_KEYWORDS			= 'keywords';
	
	/**
	 * Constructor
	 *
	 * @param   JDatabase  &$db  A database connector object
	 *
	 * @since   11.1
	 */
	public function __construct(&$db)
	{
		parent::__construct('#__jspace_metadata', 'id', $db);
	}
	
	public function fillDefault() {
		switch( $this->name ) {
			case self::METADATA_AUTHOR:
				$item = $this->getItem();
				$user = JUser::getInstance($item->user_id);
				$this->value = $user->name;
				break;
			case self::METADATA_INSTITUTION:
				$item = $this->getItem();
				$profile = JUserHelper::getProfile($item->user_id);
				$this->value = $profile->jspace['institution'];
				break;
			default:
				break;
		}
	}
	
	public function getItem() {
		$item = JTable::getInstance('item', 'JSpaceTable');
		if( !$item->load($this->item_id) ) {
			//should never happen
			throw new Exception(JText::_("JLIB_JSPACE_ERROR_METADATA_LOAD_ITEM_FAILED"));
		}
		return $item;
	}
    
    public function delete() {
        parent::delete();
    }
    
    public function setArchive() {
    	$this->archive = 1;
    	$this->store();
    }
    
    public function unsetArchive() {
    	$this->archive = 0;
    	$this->store();
    }
    
    /**
     * Some metadata objects may store many values, e.g. CSV. 
     * In some cases (e.g. convert to crosswalk values) values have to be returned separately.
     * 
     * @return array
     */
    public function getValues() {
    	switch( $this->name ) {
    		case self::METADATA_KEYWORDS:
		    	$values = explode(",", $this->value );
    			break;
    		default:
		    	$values = $this->getValueArray();
    			break;
    	}
    	return $values;
    }
    
    /**
     * Set value. If array given, implode it with "|".
     * 
     * @param unknown_type $value
     */
    public function setValue( $value ) {
    	if( is_array($value) ) {
    		$value = implode("|", $value);
    	}
    	$this->value = $value;
    }
    
    /**
     * Return value as array. Value exploded by "|".
     * 
     * @return array 
     */
    public function getValueArray() {
    	return explode("|", $this->value);
    }
	
// 	/**
// 	 * Some of metadata types are enum.
// 	 */
// 	public static function options( $type ) {
// 		$ret = array();
// 		switch( $type ) {
// 			case self::METADATA_MATERIAL_TYPE:
// 				$ret = array(
// 					"Microsoft PowerPoint presentation", 
// 					"3D File",
// 					"Image Set", 
// 				);
// 				break;
// 			case self::METADATA_TYPE:
// 				$ret = array(
// 					"Assessment",
// 					"Assignment", 
// 					"Building Blocks",
// 					"Case Study",
// 					"Collection",
// 					"Data Set",
// 					"Development Tool", 
// 					"Drill and Practice",
// 					"ePortfolio",
// 					"Online Course", 
// 					"Open Journal-Article", 
// 					"Open Textbook",
// 					"Presentation / Lecture", 
// 					"Quiz / Test",
// 					"Reference Material",
// 					"Simulation",
// 					"Social Networking Tool",
// 					"Tutorial",
// 					"Workshop and Training",				
// 				);
// 				break;
// 			default:
// 				break;
// 		}
// 		return $ret;
// 	}
	
	public function __toString() {
		return (string)$this->value;
	}
}
