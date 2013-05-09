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
jimport('jspace.database.table.bundle');

/**
 * Items table
 *
 * @package     JSpace
 * @subpackage  Table
 * @since       11.1
 */
class JSpaceTableBitstream extends JTable
{
	const ASSOCIATEDFILE_GET_PLAIN = 0;
	const ASSOCIATEDFILE_GET_PATH = 1;
	const ASSOCIATEDFILE_GET_URL = 2;
	
	/**
	 * Constructor
	 *
	 * @param   JDatabase  &$db  A database connector object
	 *
	 * @since   11.1
	 */
	public function __construct(&$db)
	{
		parent::__construct('#__jspace_bitstreams', 'id', $db);
	}
	
	/**
	 * 
	 * @throws Exception
	 * @return JSpaceTableBundle
	 */
	public function getBundle() {
		$bundle = JTable::getInstance('bundle', 'JSpaceTable');
		if( !$bundle->load($this->bundle_id) ) {
			//should never happen
			throw new Exception(JText::_("JLIB_JSPACE_ERROR_BITSTREAM_LOAD_BUNDLE_FAILED"));
		}
		return $bundle;
	}
	
	/**
	 * Delete file along with database entry.
	 * (non-PHPdoc)
	 * @see JTable::delete()
	 */
	public function delete($pk = null) {
		$path = $this->getPath();
		if( JFile::exists($path) ) {
			JFile::delete($path);
		}
		foreach( $this->getAssociatedFilesList() as $key ) {
			$file = $this->getAssociatedFile($key, self::ASSOCIATEDFILE_GET_PATH);
			if( JFile::exists($file) ) {
				JFile::delete($file);
			}
		}
		$ret = parent::delete($pk);
		return $ret;
	}

	/**
	 * Should be getUrl
	 * @return string
	 */
	public function getBitstreamUrl($subDir='/') {
		$file = $this->file;
		$bundle = $this->getBundle();
		$url = JURI::root() . str_replace(".", "/", $bundle->getDir()) . $subDir . $file;
		return $url;
	}
	
	/**
	 * Get path to bitstream file.
	 * @return string
	 */
	public function getPath() {
		$file = $this->file;
		$bundle = $this->getBundle();
		$path = $bundle->getPath() . $file;
		return $path;
	}
	
	/**
	 * Add additional metadata for bitstream. 
	 * 
	 * @param string $key
	 * @param string $val
	 */
	public function setMetadata( $key, $val ) {
		$meta = json_decode($this->metadata);
		if(empty($meta)){
			$meta = new stdClass();
		}
		$meta->$key = $val;
		$this->metadata = json_encode($meta);
	}
	
	/**
	 * Get additional metadata from bitstream.
	 * 
	 * @param string $key
	 */
	public function getMetadata( $key ) {
		$meta = json_decode($this->metadata);
		return $meta->$key;
	}
	
	/**
	 * Get array of params that needs to be copied.
	 * @author Michał Kocztorz
	 * @return array
	 */
	public function getCopyParams() {
		return array(
				'metadata'	=> $this->metadata
		);
	}
	
	/**
	 * Associate extra file to bitstream. Extra files are not part of bitstream. 
	 * Used for thumbnails in file bundles.
	 *   
	 * @author Michał Kocztorz
	 * @param string $key string to set or get the associated by
	 * @param string $file path to the file relative to bundle directory.
	 */
	public function setAssociatedFile($key, $filePath) {
		$associatedFiles = $this->getMetadata('associatedFiles');
		$associatedFiles = is_array($associatedFiles) ? $associatedFiles : array();
		$associatedFiles[$key] = $filePath;
		$this->setMetadata('associatedFiles',$associatedFiles);
		$this->store();
	}
	
	/**
	 * 
	 * @author Michał Kocztorz
	 * @param string $key
	 */
	public function getAssociatedFile($key, $get = null) {
		if( is_null($get) ) {
			$get = self::ASSOCIATEDFILE_GET_PLAIN;
		}
		$associatedFiles = $this->getMetadata('associatedFiles');
		$associatedFiles = is_object($associatedFiles) ? $associatedFiles : new stdClass();
		if( !isset($associatedFiles->$key) ) {
			return null;
		}
		switch( $get ) {
			case self::ASSOCIATEDFILE_GET_PLAIN:
				return $associatedFiles->$key;
				break;
			case self::ASSOCIATEDFILE_GET_PATH:
				return $this->getBundle()->getPath() . $associatedFiles->$key;
				break;
			case self::ASSOCIATEDFILE_GET_URL:
				return $this->getBundle()->getUrl() . str_replace(DS, "/", $associatedFiles->$key);
				break;
		}
	}
	
	/**
	 * Create a list of keys of associated files.
	 * @author Michał Kocztorz
	 * @return array
	 */
	public function getAssociatedFilesList() {
		$ret = array();
		$associatedFiles = $this->getMetadata('associatedFiles');
		if( is_object($associatedFiles) ) {
			$associatedFiles = get_object_vars($associatedFiles);
		}
		if( is_array($associatedFiles) ) {
			$ret = array_keys($associatedFiles);
		}
		return $ret;
		
	}
	
/**
 * Bitstream/bundle specyfic methods.
 */
	/**
	 * For bitstream in bundles:
	 * 	JSpaceTableBundle::BUNDLETYPE_ITEM_LETTERBOX
	 * 	JSpaceTableBundle::BUNDLETYPE_ITEM_THUMBNAIL
	 */
	public function resizeImage() {
		jimport('jspace.image.image');
		$bundle = $this->getBundle();
		switch( $bundle->type ) {
			case JSpaceTableBundle::BUNDLETYPE_ITEM_LETTERBOX:
			case JSpaceTableBundle::BUNDLETYPE_ITEM_THUMBNAIL:
				$item = $this->getBundle()->getItem();
				$tmpThumbnailBundle = $item->getBundle(JSpaceTableBundle::BUNDLETYPE_TMP_THUMBNAIL);
				$tmpThumbnailBitstream = $tmpThumbnailBundle->getPrimaryBitstream();
				$imgPath = $tmpThumbnailBitstream->getPath();
				$selection = $tmpThumbnailBitstream->getMetadata('setSelect'); //left, top, width, height of current selection
				
				jimport('jspace.image.thumbnail');
				$thumbnail = new JSpaceThumbnail($imgPath, $selection);
				if( $bundle->type == JSpaceTableBundle::BUNDLETYPE_ITEM_LETTERBOX ) {
					$tmpdir = $thumbnail->letterboxFile;
				}
				else {
					$tmpdir = $thumbnail->thumbnailFile;
				}
				
				/**
				 * Delete current file and move cropped one.
				 */
				$path = $this->getPath();
				if( JFile::exists($path) ) {
					if( !JFile::delete($path) ) {
						throw new Exception(JText::_("JLIB_JSPACE_ERROR_BITSTREAM_CANT_DELETE_PREVIOUS_FILE"));
					}
					if( !JFile::move($tmpdir, $path)) {
						throw new Exception(JText::_("JLIB_JSPACE_ERROR_BITSTREAM_CANT_MOVE_CROPPED_FILE"));
					}
				}
				break;
			default:
				throw new Exception(JText::_("JLIB_JSPACE_ERROR_BITSTREAM_RESIZE_ATTEMPT_ON_WRONG_BITSTREAM"));
				break;
		}
	}
	

	/**
	 * For bitstream in bundles:
	 * 	JSpaceTableBundle::BUNDLETYPE_TMP_THUMBNAIL
	 */
	public function selectChanged() {
		$bundle = $this->getBundle();
		if( $bundle->type == JSpaceTableBundle::BUNDLETYPE_TMP_THUMBNAIL ) {
			$item = $this->getBundle()->getItem();
			$item->getBundle(JSpaceTableBundle::BUNDLETYPE_ITEM_LETTERBOX)->getPrimaryBitstream()->resizeImage();
			$item->getBundle(JSpaceTableBundle::BUNDLETYPE_ITEM_THUMBNAIL)->getPrimaryBitstream()->resizeImage();
		}
		else {
			throw new Exception(JText::_("JLIB_JSPACE_ERROR_BITSTREAM_RESIZE_ATTEMPT_ON_WRONG_BITSTREAM"));
		}
	}
}
