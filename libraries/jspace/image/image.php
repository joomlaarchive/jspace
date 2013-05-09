<?php
/**
 * A JSpace JSpaceImage class.
 * Extension to JImage. Allows outputting image to browser.
 * 
 * @package		JSpace
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

class JSpaceImage extends JImage
{
	public function outputJpg() {
		// Make sure the resource handle is valid.
		if (!$this->isLoaded())
		{
			throw new LogicException('No valid image was loaded.');
		}
		
		imagejpeg($this->handle);
	}
	
	/**
	 * Get image width/height if it was resized to this size using JImage::SCALE_INSIDE.
	 * @author Michał Kocztorz
	 * @param number $width
	 * @param number $height
	 * @return Object
	 */
	public function scaleInsideDimensions($width, $height) {
		return $this->prepareDimensions($width, $height, JImage::SCALE_INSIDE);
	}

	/**
	 * Get image width/height if it was resized to this size using JImage::SCALE_OUTSIDE.
	 * @author Michał Kocztorz
	 * @param number $width
	 * @param number $height
	 * @return Object
	 */
	public function scaleOutsideDimensions($width, $height) {
		return $this->prepareDimensions($width, $height, JImage::SCALE_OUTSIDE);
	}
}