<?php
/**
 * Thumbnail creation
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
jimport('jspace.image.image');

class JSpaceThumbnail
{
	/**
	 * 
	 * @var string
	 */
	public $uploadedFile 	= '';
	/**
	 * 
	 * @var string
	 */
	public $letterboxFile	= '';
	/**
	 * 
	 * @var string
	 */
	public $thumbnailFile	= '';
	
	/**
	 * 
	 * @var string
	 */
	public $baseDir 		= '';
	
	public static $done = array();
	
	/**
	 * The image is not physically cropped on the fly when user selects his thumbnail. It has to be done deliberately
	 * due to performance (it should be done once on save).
	 * 
	 * @param unknown_type $uploadedFile
	 * @param unknown_type $croperSize
	 * @param unknown_type $viewSize
	 * @param unknown_type $letterboxSize
	 * @throws Exception
	 */
	public function __construct(
							$uploadedFile,	//path to tmp upoaded file, thumbnail and letterbox will be created in the same dir 
							$croperSize 	= array(0,0,202,125), 	//the selection size [left,top,width,height]
							$viewSize 		= array('width'=>500,'height'=>500),	//the size of cropper on website, needed to scale the cropperSIze
							$letterboxSize 	= array('width'=>202,'height'=>125)	//the size of letterbox, thumbnail is square with side of letterbox's height
						) {
		$this->baseDir = dirname($uploadedFile) . DS;
		$this->uploadedFile = $uploadedFile;
		$this->letterboxFile = $this->baseDir . JFile::stripExt(JFile::getName($this->uploadedFile)) . '_letterbox.' . JFile::getExt($this->uploadedFile);
		$this->thumbnailFile = $this->baseDir . JFile::stripExt(JFile::getName($this->uploadedFile)) . '_thumbnail.' . JFile::getExt($this->uploadedFile);
		$key = md5($this->uploadedFile);
		if( isset(self::$done[$key]) ) {
			return;//already done
		}
		self::$done[$key] = true;
		
		/*
		 * We need to calculate te images width and height that is available on site. Then we can calculate
		 * values available in JSpaceTableBundle::BUNDLETYPE_TMP_THUMBNAIL's bitstream's metatata's setSelect
		 * back to oryginal image.
		 */
		$image = new JSpaceImage();
		$image->loadFile($uploadedFile);
		$viewWidth = $viewSize['width'];
		$viewHeight = $viewSize['height'];
		$dimensions = $image->scaleInsideDimensions($viewWidth, $viewHeight);
		$imgPhysicalHeight = $image->getHeight();
		$imgPhysicalWidth = $image->getWidth();
		
		$widthFactor = (double)$imgPhysicalWidth / (double)$dimensions->width;
		$heightFactor = (double)$imgPhysicalHeight / (double)$dimensions->height;
		
		/*
		 * Calculating the left, top, width and height of selection resized on image physical size.
		 * LETTERBOX
		*/
// 		if( $bundle->type == JSpaceTableBundle::BUNDLETYPE_ITEM_LETTERBOX ) {
			$physicalSelectionWidth 	= $croperSize[2] * $widthFactor;
			$physicalSelectionLeft 		= $croperSize[0] * $widthFactor;
			$physicalSelectionHeight 	= $croperSize[3] * $heightFactor;
			$physicalSelectionTop 		= $croperSize[1] * $heightFactor;
			$imageCropped = $image->crop($physicalSelectionWidth, $physicalSelectionHeight, $physicalSelectionLeft, $physicalSelectionTop, true);
			if( !JFolder::exists($this->baseDir) ) {
				if( !JFolder::create( $this->baseDir ) ) {
					throw new Exception(JText::_("JLIB_JSPACE_ERROR_BITSTREAM_CANT_CREATE_TMP_LOCATION"));
				}
			}
			$imageCropped = $imageCropped->resize($letterboxSize['width'], $letterboxSize['height'],JImage::SCALE_FILL);
			$imageCropped->toFile($this->letterboxFile);
// 		}
// 		else {
			/*
			 * THUMBNAIL (square)
			 */
			$physicalSelectionWidth 	= $croperSize[2] * $widthFactor;
			$physicalSelectionLeft 		= $croperSize[0] * $widthFactor;
			$physicalSelectionHeight 	= $croperSize[3] * $heightFactor;
			$physicalSelectionTop 		= $croperSize[1] * $heightFactor;
			//cutting a square from this rectangle. Height is the size of rectangle side.
			$overWidth = $physicalSelectionWidth - $physicalSelectionHeight;
			$physicalSelectionLeft += $overWidth / 2.0;
			$imageCropped = $image->crop($physicalSelectionHeight, $physicalSelectionHeight, $physicalSelectionLeft, $physicalSelectionTop, true);//2x height = OK, it's a rectangle!
			$imageCropped = $imageCropped->resize($letterboxSize['height'], $letterboxSize['height'],JImage::SCALE_FILL);//2x height = OK, it's a rectangle!
			$imageCropped->toFile($this->thumbnailFile);
// 		}
	}
}