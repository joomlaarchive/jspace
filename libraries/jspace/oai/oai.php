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

JLoader::discover("JSpaceOAI", JPATH_SITE . "/libraries/jspace/oai/");

/**
 * @author Michał Kocztorz
 * @package     JSpace
 * @subpackage  OAI
 */
class JSpaceOAI extends JObject
{
	const DATE_GRANULARITY_DAY = 'Y-m-d';
	const DATE_GRANULARITY_SECOND = 'Y-m-d\TH:i:s\Z';
	
	protected static $_disseminateFormats = array(
		'oai_dc'
	);
	
	public static function adminEmails() {
		$config = JSpaceFactory::getConfig();
		$admins = $config->get('oai_administrators', "");
		$admins = explode(";", $admins);
		$admins = array_map('trim', $admins);
		return $admins;
	}
	
	/**
	 * Get set id by category.
	 * 
	 * @param JSpaceRepositoryCategory $category
	 * @return $id
	 */
	public static function getSetID( JSpaceRepositoryCategory $category ) {
		$setId = array();
		while( !$category->isRoot() ) {
			$setId[] = $category->getId();
			$category = $category->getParent();
		}
		$setId[] = $category->getId();
		$setId = array_reverse($setId);
		return implode(':', $setId);
	}
	
	/**
	 * Get array of tested disseminate formats.
	 * 
	 * @return array
	 */
	public static function getAllDisseminateFormats() {
		$formats = array();
		foreach( self::$_disseminateFormats as $type ) {
			try {
				$formats[ $type ] = JSpaceOAIDisseminateFormat::getInstance( $type );
			}
			catch( Exception $e ) {
			}
		}
		return $formats;
	}
}


