<?php
/**
 * A JSpace factory class.
 * 
 * @package		JSpace
 * @copyright	Copyright (C) 2012-2014 KnowledgeARC. All rights reserved.
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
 * Hayden Young					<haydenyoung@wijiti.com> 
 * 
 */
defined('JPATH_PLATFORM') or die;

class JSpaceFactory
{
	const JSPACE_NAME = 'com_jspace';
	
	/**
	 * 
	 * @return JRegistry
	 */
	public static function getConfig()
	{
		$config = new JRegistry();
		$component = JComponentHelper::getComponent(self::JSPACE_NAME);
		if ($component->enabled) {
			$config = $component->params;
		}
		return $config;
	}
	
	/**
	 * Gets an instance of the JSpaceMetadataCrosswalk class. 
	 *
	 * @param 	JRegistry	$metadata
	 * @param	array		$config
	 * @return	JSpaceMetadataCrosswalk An instance of JSpaceMetadataCrosswalk class.
	 */
	public static function getCrosswalk($metadata, $config)
	{
		if (!($crosswalk = JArrayHelper::getValue($config, 'name', null)))
		{
			throw new InvalidArgumentException("LIB_JSPACE_EXCEPTION_NO_NAME");
		}

		jimport('jspace.metadata.crosswalk');
		
		return new JSpaceMetadataCrosswalk($metadata, $crosswalk);
	}
}