<?php
/**
 * A helper that provides a number of metadata handlers.
 * 
 * @author		$LastChangedBy$
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
 * Hayden Young					<haydenyoung@wijiti.com> 
 * 
 */

class JSpaceMetadata
{
	public static function getElementAsString($metadata, $name, $scheme = null, $separator = "; ")
	{
		$key = $name . ($scheme ? ".".$scheme : $scheme);		
		return implode(";", JArrayHelper::getValue(self::toArray($metadata), $key, array()));
	}
	
	public static function getElementAsArray($metadata, $name, $scheme = null)
	{
		$key = $name . ($scheme ? ".".$scheme : $scheme);
		return JArrayHelper::getValue(self::toArray($metadata), $key, array());
	}
	
	/**
	 * Gets a list of meta tags as an array.
	 * 
	 * @param string $metadata Valid meta data as HTML.
	 */
	public static function toArray($metadata)
	{
		$array = array();
		
		$document = new DOMDocument();
		$document->loadHTML($metadata);

		foreach ($document->getElementsByTagName("meta") as $node) {
			$name = $node->getAttribute("name");
			$scheme = $node->getAttribute("scheme");
			
			$key = $name . ($scheme ? ".".$scheme : $scheme);
			
			if (!array_key_exists($key, $array)) {
				$array[$key] = array();
				
				if ($scheme && !array_key_exists($scheme, $array[$key])) {
					$array[$key] = array();
				}
			}

			$array[$key][] = $node->getAttribute("content");
		}

		return $array;
	}
}