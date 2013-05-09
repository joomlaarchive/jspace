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

// jimport('joomla.database.tableasset');
jimport('joomla.database.table');
jimport('jspace.database.table.bitstream');
jimport('joomla.application.component.helper');

/**
 * Items table
 *
 * @package     JSpace
 * @subpackage  Table
 * @since       11.1
 */
class JSpaceTableBundle extends JTable
{
	const BUNDLETYPE_ORIGINAL		= "ORIGINAL";
	const BUNDLETYPE_PREVIEW		= "PREVIEW";
	const BUNDLETYPE_THUMBNAIL		= "THUMBNAIL";

	const BUNDLETYPE_ITEM_THUMBNAIL			= "ITEM_THUMBNAIL";
	const BUNDLETYPE_ITEM_LETTERBOX			= "ITEM_LETTERBOX";
	const BUNDLETYPE_TMP_THUMBNAIL			= "TMP_THUMBNAIL";
	
	/**
	 * Constructor
	 *
	 * @param   JDatabase  &$db  A database connector object
	 *
	 * @since   11.1
	 */
	public function __construct(&$db)
	{
		parent::__construct('#__jspace_bundles', 'id', $db);
	}
	
	/**
	 * 
	 * @return JSpaceTableBitstream
	 */
	public function getPrimaryBitstream() {
		$db = $this->getDbo();
		$query = $db->getQuery(true);
		$query->select('*')->from('#__jspace_bitstreams')->where('bundle_id=' . $db->quote($this->id))->order('ordering','asc');
		$db->setQuery($query);
		$obj = $db->loadObject();
		$bitstream = JTable::getInstance('bitstream', 'JSpaceTable');
		if( !is_null($obj) ) {
			$bitstream->bind( $obj );
		}
		return $bitstream;
	}
	
	/**
	 * Return list of bitstreams.
	 * 
	 * @return array of JSpaceTableBitstream
	 */
	public function getBitstreams() {
		$db = $this->getDbo();
		$query = $db->getQuery(true);
		$query->select('*')->from('#__jspace_bitstreams')->where('bundle_id=' . $db->quote($this->id))->order('ordering asc');
		$db->setQuery($query);
		$obj = $db->loadObjectList();
		$bitstreams = array();
		foreach($obj as $row) {
			$bitstream = JTable::getInstance('bitstream', 'JSpaceTable');
			$bitstream->bind( $row );
			$bitstreams[] = $bitstream;
		}
		return $bitstreams;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see JTable::delete()
	 */
	public function delete() {
		$this->clearBitstreams();
		JFolder::delete($this->getPath());
		parent::delete();
	}
	
	/**
	 * Delete all bitstreams.
	 */
	public function clearBitstreams() {
		$bitstreams = $this->getBitstreams();
		foreach( $bitstreams as $bitstream ) {
			$bitstream->delete();
		}
	}
	
	/**
	 * Copies given file to bundle location.
	 * @param string $src
	 */
	public function prepareFile( $src ) {
		$dest = $this->getPath() . JFile::getName($src);
		if( $src==$dest ) {
			return $dest;
		}
		if( JFile::copy($src, $dest) ) {
			return $dest;
		}
		throw new Exception(JText::_("LIB_JSPACE_ERROR_SAVING_UPLOADED_FILE" . ' ' . $src . ' | ' . $dest));
	}
	
	/**
	 * Creates a bitstream based on file name.
	 * 
	 * @author Michał Kocztorz
	 * @param string $file
	 * @param array $params
	 * @return JSpaceTableBitstream
	 */
	public function addBitstream( $file, $params=array() ) {
		//Some bundles should contain only one bitstream
		if( in_array($this->type, array(self::BUNDLETYPE_TMP_THUMBNAIL, self::BUNDLETYPE_ITEM_LETTERBOX, self::BUNDLETYPE_ITEM_THUMBNAIL)) ) {
			//can contain only one bitstream
			$this->clearBitstreams();
		}
		$bitstream = JTable::getInstance('bitstream', 'JSpaceTable');
		$file = $this->prepareFile($file);
		
		switch( $this->type ) {
			case self::BUNDLETYPE_TMP_THUMBNAIL:
				$bitstream->setMetadata("setSelect", array(0,0,202,125));
				break;
		}
		
		$bitstream->name = JFile::getName($file);
		$bitstream->file = JFile::getName($file);
		$bitstream->bundle_id = $this->id;
		$bitstream->ordering = $bitstream->getNextOrder("bundle_id=" . $this->id);
		$bitstream->bind( $params );
		$bitstream->store();
		//after saving
		switch( $this->type ) {
			case self::BUNDLETYPE_TMP_THUMBNAIL:
				//creating resized bitstreams
				$item = $this->getItem();
				$thumbnail = $item->getBundle(JSpaceTableBundle::BUNDLETYPE_ITEM_THUMBNAIL);
				$thumbnail->addBitstream($file)->resizeImage();
				$letterbox = $item->getBundle(JSpaceTableBundle::BUNDLETYPE_ITEM_LETTERBOX);
				$letterbox->addBitstream($file)->resizeImage();
				break;
		}
		return $bitstream;
	}
	
	
	/**
	 * 
	 * @throws Exception
	 * @return JSpaceTableItem
	 */
	public function getItem() {
		$item = JTable::getInstance('item', 'JSpaceTable');
		if( !$item->load($this->item_id) ) {
			//should never happen
			throw new Exception(JText::_("JLIB_JSPACE_ERROR_BUNDLE_LOAD_ITEM_FAILED"));
		}
		return $item;
	}
	
	/**
	 * Return directory root where files are stored. 
	 */
	public function getDir() {
		return JComponentHelper::getParams('com_jspace')->get('storage_directory') . "." . $this->id;
	}
	
	/**
	 * Return full path to bundle files directory.
	 */
	public function getPath() {
		$path = JPATH_SITE . DS .str_replace(".", DS, $this->getDir()) . DS;
		if( !JFolder::exists($path) ) {
			if( !JFolder::create($path) ) {
				throw new Exception(JText::_("JLIB_JSPACE_ERROR_BUNDLE_CREATE_DIR_FAILED"));
			}
		}
		return $path;
	}
	
	/**
	 * Return full url to bundle files directory.
	 */
	public function getUrl() {
		$path = JURI::base() .str_replace(".", "/", $this->getDir()) . "/";
		return $path;
	}
	
	/**
	 * 
	 * @param JSpaceTableBitstream $bitstream
	 */
	public function cloneBitstream( $bitstream ) {
		$addedBitstream = $this->addBitstream( $bitstream->getPath(), $bitstream->getCopyParams() );
		$associatedFiles = $bitstream->getAssociatedFilesList();
		foreach($associatedFiles as $key){
			$addedBitstream->setAssociatedFile($key, $bitstream->getAssociatedFile($key)); //copied db value, not file
			$dest = $addedBitstream->getAssociatedFile($key, JSpaceTableBitstream::ASSOCIATEDFILE_GET_PATH);
			JFolder::create(dirname($dest));
			if( !JFile::copy($bitstream->getAssociatedFile($key, JSpaceTableBitstream::ASSOCIATEDFILE_GET_PATH), $dest) ) {
				throw new Exception(JText::_("JLIB_JSPACE_ERROR_BUNDLE_CLONING_BITSTREAM_FAILED_ASSOCIATED_FILE_COPY_FAILED"));
			}
		}
	}
	
	/**
	 * Debug function.
	 * @return string
	 */
	public function _getDisplayType() {
		$ret = 'n/a';
		switch( $this->type ) {
			case self::BUNDLETYPE_ORIGINAL:			$ret = 'BUNDLETYPE_ORIGINAL'; break;
			case self::BUNDLETYPE_TMP_THUMBNAIL:	$ret = 'BUNDLETYPE_TMP_THUMBNAIL'; break;
			case self::BUNDLETYPE_ITEM_LETTERBOX:	$ret = 'BUNDLETYPE_ITEM_LETTERBOX'; break;
			case self::BUNDLETYPE_ITEM_THUMBNAIL:	$ret = 'BUNDLETYPE_ITEM_THUMBNAIL'; break;
			case self::BUNDLETYPE_PREVIEW:			$ret = 'BUNDLETYPE_PREVIEW'; break;
			case self::BUNDLETYPE_THUMBNAIL:		$ret = 'BUNDLETYPE_THUMBNAIL'; break;
		}
		return $ret;
	}
}
