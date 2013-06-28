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
jimport('jspace.database.table.bundle');
jimport('jspace.database.table.metadata');
jimport('jspace.database.table.items');
jimport('jspace.database.table.bitstream');
jimport('joomla.database.table.user_profiles');
jimport('jspace.crosswalk.mapper');
jimport('jspace.image.image');
require_once(JPATH_ROOT.DS.'libraries'.DS.'joomla'.DS.'filesystem'.DS.'archive'.DS.'zip.php');
jimport('joomla.utilities.simplexml');
/**
 * Items table
 *
 * @package     JSpace
 * @subpackage  Table
 * @since       11.1
 */
class JSpaceTableItem extends JTable
{
	const ITEMSTATE_PARTIAL		= 50;
	const ITEMSTATE_UPDATED		= 80;
	const ITEMSTATE_SAVED		= 100;
	const ITEMSTATE_PUBLISHED	= 110;
	const ITEMSTATE_INWORKFLOW	= 150;
	const ITEMSTATE_DELETED		= 999;
	const ITEMSTATE_TEMPORARY	= 888;	//item loaded back from repo
	
	/**
	 * This value is not saved in db. 
	 * It is set only when finished editing partial item.
	 * @var unknown_type
	 */
	protected $_isNew = false;
    
    /**
	 * This value is not saved in db. 
	 * It is set only when archive is validated.
	 * @var array
	 */
	protected $_validationErrors = array();

	/**
	 * Constructor
	 *
	 * @param   JDatabase  &$db  A database connector object
	 *
	 * @since   11.1
	 */
	public function __construct(&$db)
	{
		parent::__construct('#__jspace_items', 'id', $db);
		$this->created = date("Y-m-d H:i:s");
	}
	
	public function store($updateNulls=false) {
		$isNew = is_null($this->id);
		if( is_null($this->created) ) {
			$this->created = date("Y-m-d H:i:s");
		}
		$this->modified = date("Y-m-d H:i:s");
		$success = parent::store($updateNulls);
		if( $isNew ) {
			//creating minimum structure
			//1. Create one ORIGINAL bundle (get creates it if not found)
			$this->getBundle(JSpaceTableBundle::BUNDLETYPE_ORIGINAL);
			//2. Create one TMP_THUMBNAIL, ITEM_THUMBNAIL, LETTERBOX_THUMBNAIL bundles
			$this->getBundle(JSpaceTableBundle::BUNDLETYPE_TMP_THUMBNAIL);
			$this->getBundle(JSpaceTableBundle::BUNDLETYPE_ITEM_THUMBNAIL);
			$this->getBundle(JSpaceTableBundle::BUNDLETYPE_ITEM_LETTERBOX);
		}
		return $success;
	}
	
	/**
	 * 
	 * @param unknown_type $type
	 * @return JSpaceTableBundle
	 */
	public function getBundle($type) {
		$table = JTable::getInstance("bundle", "JSpaceTable");
		$where = array(
				"item_id" 	=> $this->id,
				"type" 	=> $type
		);
		if( !$table->load($where) ) {
			$table->item_id = $this->id;
			$table->type = $type;
			$table->store();
		}
		return $table;
	}
	
	/**
	 * Get array of bundle objects.
	 * @author Michał Kocztorz
	 * @return array
	 */
	public function getBundles() {
		$db = $this->getDbo();
		$query = $db->getQuery(true);
		$query->select('*')->from('#__jspace_bundles')->where('item_id=' . $db->quote($this->id));
		$db->setQuery($query);
		$obj = $db->loadObjectList();
		$bundles = array();
		foreach($obj as $row) {
			$bundle = JTable::getInstance('bundle', 'JSpaceTable');
			$bundle->bind( $row );
			$bundles[] = $bundle;
		}
		return $bundles;
	}
	
	/**
	 * Add an image to JSpaceTableBundle::BUNDLETYPE_TMP_THUMBNAIL.
	 * @author Michał Kocztorz
	 * @param string $imgPath
	 * @return JSpaceTableBitstream
	 */
	public function addThumbnail( $imgPath ) {
		$bundle = $this->getBundle(JSpaceTableBundle::BUNDLETYPE_TMP_THUMBNAIL);
		/*
		 * Bundle of this type will make sure that there is only one bitstream in it.
		 * When adding a bitstream it will also create BUNDLETYPE_ITEM_THUMBNAIL and BUNDLETYPE_ITEM_LETTERBOX.
		 */
		$bitstream = $bundle->addBitstream($imgPath);
		return $bitstream;
	}
	
	public function deleteThumbnail() {
		$this->getBundle(JSpaceTableBundle::BUNDLETYPE_TMP_THUMBNAIL)->clearBitstreams();
		$this->getBundle(JSpaceTableBundle::BUNDLETYPE_ITEM_THUMBNAIL)->clearBitstreams();
		$this->getBundle(JSpaceTableBundle::BUNDLETYPE_ITEM_LETTERBOX)->clearBitstreams();
	}
	
	/**
	 * @author Michał Kocztorz
	 * Tests if item has a thumbnail.
	 * @param int $type
	 * @return bool
	 */
	public function hasThumbnail( $type ) {
		if( in_array($type, array(JSpaceTableBundle::BUNDLETYPE_ITEM_LETTERBOX,JSpaceTableBundle::BUNDLETYPE_ITEM_THUMBNAIL) ) ) {
			return JFile::exists( $this->getBundle($type)->getPrimaryBitstream()->getPath() );
		}
		return false;
	}
	
	/**
	 * 
	 * @param int $type
	 * @param bool $returnUrl
	 * @return string
	 */
	public function getThumbnail( $type, $returnUrl = true ) {
		if( in_array($type, array(JSpaceTableBundle::BUNDLETYPE_ITEM_LETTERBOX,JSpaceTableBundle::BUNDLETYPE_ITEM_THUMBNAIL) ) ) {
			if( $returnUrl ) {
				return $this->getBundle($type)->getPrimaryBitstream()->getBitstreamUrl();
			}
			else {
				return $this->getBundle($type)->getPrimaryBitstream()->getPath();
			}
		}
		return "";
	}
	
	/**
	 * Return metadata of given type associated with Item.
	 * 
	 * @param string $type
	 * @return JSpaceTableMetadata
	 */
	public function getMetadata( $type ) {
		$metadata = JTable::getInstance("metadata", "JSpaceTable");
		$where = array(
			"name"		=> $type,
			"item_id"	=> $this->id
		);
		
		if( !$metadata->load($where) ) {
			$metadata->item_id = $this->id;
			$metadata->name = $type;
			
			//type specyfic init
			$metadata->fillDefault();
			
			$metadata->store();
		}
		
		return $metadata;
	}
	
	/**
	 * Get array of metadata objects. Parameter allows selecting only metedeta ment to be archived.
	 * 
	 * I know this is not valid english, but I'll leave this name anyway.
	 * 
	 * @author Michał Kocztorz
	 * @param $onlyForArchive
	 * @return array
	 */
	public function getMetadatas( $onlyForArchive=false ) {
		$db = $this->getDbo();
		$query = $db->getQuery(true);
		$query->select('*')->from('#__jspace_metadata')->where('item_id=' . $db->quote($this->id));
		if( $onlyForArchive ) {
			$query->where('archive=1');
		}
		$db->setQuery($query);
		$obj = $db->loadObjectList();
		$metadatas = array();
		foreach($obj as $row) {
			$metadata = JTable::getInstance('metadata', 'JSpaceTable');
			$metadata->bind( $row );
			$metadatas[] = $metadata;
		}
		return $metadatas;
	}
	
	/**
	 * Set value in metadata table.
	 * 
	 * @param string $type
	 * @param string $value
	 */
	public function setMetadata( $type, $value, $extra = array() ) {
		$metadata = $this->getMetadata( $type );
		$metadata->setValue($value);
		foreach( $extra as $key => $val ) {
			$metadata->$key = $val;
		}
		$metadata->store();
	}
	
	/**
	 * Some actions have to be done before validation starts.
	 * @author Michał Kocztorz 
	 */
	protected function _preprocessValidation() {
		/*
		 * The PREVIEW bundle files should be resized and copied to THUMBNAIL bundle in the same order.
		 */
		$preview = $this->getBundle(JSpaceTableBundle::BUNDLETYPE_PREVIEW);
		$thumbnail = $this->getBundle(JSpaceTableBundle::BUNDLETYPE_THUMBNAIL);
		$thumbnail->clearBitstreams();
		
		$bitstreams = $preview->getBitstreams();
		$tmp = JPATH_SITE . "/tmp/thumbnailCreation/" . $this->id . "/";
		if( !JFolder::exists($tmp) ) {
			JFolder::create($tmp);
		}
		$ret = array();
		
		$error = false;
		if( JFolder::exists($tmp) ) {
			foreach( $bitstreams as $bitstream ) {
				$path = $bitstream->getPath();
				try {
					if( JFile::exists($path) ) {
						$image = new JSpaceImage( $path );
						$width = 80;
						$height = 80;
						$size = $image->scaleOutsideDimensions($width, $height);
						$left = round(( $size->width == $width ) ? 0 : ($size->width - $width) / 2 );
						$top = round(( $size->height == $height) ? 0 : ($size->height - $height) / 2);
						
						$newimage = $image->resize($width, $height, true, JImage::SCALE_OUTSIDE)->crop($width, $height, $left, $top);
						$newpath = $tmp . JFile::getName($path);
						$newimage->toFile( $newpath );
						$thumbnail->addBitstream( $newpath );
						JFile::delete($newpath);
					}
					else {
						$error = true;
					}
				}
				catch(Exception $e){
					$error = true;
				}
			}
			JFolder::delete($tmp);
			
			if( $error ) {
				$this->_validationErrors[] = JText::_("COM_JSPACE_ITEM_PREPROCESS_VALIDATION_ERROR_RESIZING_FILES"); 
			}
		}
		else {
			$this->_validationErrors[] = JText::_("COM_JSPACE_ITEM_PREPROCESS_VALIDATION_ERROR_CREATING_TMP"); 
		}
	}
    
	/**
	 * Set and return validation errors
	 * 
	 * @return array
     * @author Piotr Dolistowski
	 */
	public function validate() {
		JSpaceLog::add("Validate item id=" . $this->id, JLog::DEBUG, JSpaceLog::CAT_REPOSITORY);
		$this->_preprocessValidation();
		
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $bundles = $this->getBundles() ;
        $required = array('ORIGINAL') ;
        
        $fix = false;
        
        if ( !empty($bundles) ) {
            foreach( $bundles as $bundle) {
                $bitstreams = $bundle->getBitstreams() ;
                if ( !empty($bitstreams) ) {
                    foreach($bitstreams as $bitstream) {
                    	$filePath = $bitstream->getPath();
                        if ( file_exists($filePath) ) {
                            if ( $finfo->file($filePath) == false ) {
                            	$fix = true;
                                $this->_validationErrors[] = JText::_('COM_JSPACE_TABLE_ITEM_ARCHIVE_FILE_CORRUPTED') ;
                            }
                        } else {
                        	JSpaceLog::add("Fatal Error. File not found: " . $filePath, JLog::ERROR, JSpaceLog::CAT_REPOSITORY);
                        	$fix = true;
                            $this->_validationErrors[] = JText::_('COM_JSPACE_TABLE_ITEM_ARCHIVE_FILE_NOT_EXISTS') ;
                        }
                    }
                } else {
                    if ( in_array($bundle->type, $required) ) {
                        $this->_validationErrors[] = JText::_('COM_JSPACE_TABLE_ITEM_ARCHIVE_BITSTREAMS_EMPTY') ;
                    }
                }
            }
        } else {
            if ( in_array($bundle->type, $required) ) {
                $this->_validationErrors[] = JText::_('COM_JSPACE_TABLE_ITEM_ARCHIVE_BUNDLES_EMPTY') ;
            }
        }
        
        if( $fix ) {
        	$files = $this->fix();
        	foreach( $files as $file ) {
        		$this->_validationErrors[] = JText::sprintf('COM_JSPACE_TABLE_ITEM_ARCHIVE_FILE_CORRUPTED', $file);
        	}
        }
        
        JSpaceLog::add("Validated item id=" . $this->id . ". Found errors count=" . count($this->_validationErrors), JLog::DEBUG, JSpaceLog::CAT_REPOSITORY);
        return $this->_validationErrors ;
	}
	
	/**
	 * Some files are missing from item.
	 * Removes bitstreams from bundles where files are missing.
	 */
	public function fix() {
		JSpaceLog::add("Item fix attempt", JLog::DEBUG, JSpaceLog::CAT_REPOSITORY);
		
		$ret = array();
		
		$finfo = new finfo(FILEINFO_MIME_TYPE);
        $bundles = $this->getBundles() ;
        if ( !empty($bundles) ) {
            foreach( $bundles as $bundle) {
                $bitstreams = $bundle->getBitstreams() ;
                if ( !empty($bitstreams) ) {
                    foreach($bitstreams as $bitstream) {
                    	$filePath = $bitstream->getPath();
                        if ( !file_exists($filePath) || $finfo->file($filePath) == false ) {
                        	$ret[] = $bitstream->file;
                        	$msg  = "File not found or corrupted\n";
                        	$msg .= "\tBundle=" . $bundle->type;
                        	$msg .= "\tFile=" . $filePath;
                        	$msg .= "\tDeleting bitstream id=" . $bitstream->id;
                        	JSpaceLog::add($msg, JLog::ERROR, JSpaceLog::CAT_REPOSITORY);
                        	
                        	$bitstream->delete();
                        	JSpaceLog::add("Bitstream deleted", JLog::ERROR, JSpaceLog::CAT_REPOSITORY);
                        }
                    }
                }
            }
        }

		JSpaceLog::add("Item fix attempt DONE", JLog::DEBUG, JSpaceLog::CAT_REPOSITORY);
		return $ret;
	}

	/**
	 * Archive item in DSpace.
     * 
     * @author Piotr Dolistowski
	 */
	public function archive() {
		JSpaceLog::add("Archiving item", JLog::DEBUG, JSpaceLog::CAT_REPOSITORY);
		$repository = JSpaceFactory::getRepository();
		$id = $repository->storeItem( $this );
		if( !is_null($id) ) {
			$this->setMetadata('dspaceItemId', $id);
			$this->state = self::ITEMSTATE_DELETED;
			parent::store();
		}
		else {
			foreach( $repository->getErrors() as $error ) {
				$this->setError( $error );
			}
		}
		
		
		return $id;
	}
    
	/**
	 * (non-PHPdoc)
	 * @see JTable::delete()
	 */
    public function delete() {
    	foreach( $this->getMetadatas() as $metadata ) {
    		$metadata->delete();
    	}
    	
    	foreach( $this->getBundles() as $bundle ) {
    		$bundle->delete();
    	}
        
    	$storage_dir = $this->getStorageDir();
    	if( JFolder::exists($storage_dir) ) {
        	JFolder::delete( $storage_dir );
    	}
        parent::delete();
    }
	
	/**
	 * Finish editing item.
	 * New item's state will be changed from ITEMSTATE_PARTIAL to ITEMSTATE_SAVED.
	 * Edited item's state will be changed from ITEMSTATE_UPDATED to ITEMSTATE_SAVED and
	 * oryginal item will be deleted.
	 */
	public function finishEditing() {
        
		if( $this->state == self::ITEMSTATE_PARTIAL ) {
			$this->state = self::ITEMSTATE_SAVED;
			parent::store();//not saving entire item with bundles etc., but only this JTable
			$this->_isNew = true; //mark this instance as new item
			return true;
		}
		else if( $this->state == self::ITEMSTATE_UPDATED ) {
			/*
			 * This item is a copy of another item with state = ITEMSTATE_SAVED.
			 * To finish editing we need to:
			 * 1. Change state of this item to ITEMSTATE_SAVED 
			 * 2. Delete oryginal item
			 */
			
			//get oryginal item
			$item = $this->getOriginal();
			$this->state = self::ITEMSTATE_SAVED;
			if( !parent::store() ) {
				return false;
			} 
			if( !is_null($item) ) {
				$item->delete();
				return true;
			}
		}
		return false;
	}
	
	/**
	 * Start editing item.
	 * Creates a deep copy of an item with ITEMSTATE_UPDATED state. 
	 * If item already has a copy, return it.
	 * When a copy is done:
	 * - original_item.updated_item = copied_item.id
	 * - this => reload with copied_item.id
	 * 
	 * @return JSpaceTableItem
	 */
	public function startEditing() {
		switch( $this->state ) {
			case JSpaceTableItem::ITEMSTATE_SAVED:
				if( empty($this->updateditem_id) ) {
					return $this->_deepCopy();
				}
				else {
					$item = JTable::getInstance('item', 'JSpaceTable');
					if( $item->load( $this->updateditem_id ) ) {
// 						var_dump($item);
						return $item;
					}
					else {
						throw new Exception(JText::_('LIB_JSPACE_CANT_EDIT_UPDATED_ITEM'));
					}
				}
				break;
			case JSpaceTableItem::ITEMSTATE_UPDATED:
			case JSpaceTableItem::ITEMSTATE_PARTIAL:
				return $this;
				break;
			default:
				throw new Exception(JText::_('LIB_JSPACE_CANT_EDIT_ITEM'));
				break;
		}
	}
	

	/**
	 * Cancel editing item.
	 * Delete this item. Set to null oryginal's item updateditem_id 
	 */
	public function cancelEditing() {
		if( $this->state == self::ITEMSTATE_PARTIAL ) {
			//$this->state = self::ITEMSTATE_SAVED;
			//do nothing
			$this->delete();
		}
		else if( $this->state == self::ITEMSTATE_UPDATED ) {
			//get oryginal item
			$item = JTable::getInstance('item', 'JSpaceTable');
			$where = array('updateditem_id' => $this->id);
// 			var_dump($where);
			if( $item->load( $where ) ) {
				$item->updateditem_id = null;
				$item->store(true);
			}
			
			$this->delete();
		}
	}
	
	/**
	 * Creates a deep copy of item.
	 */
	protected function _deepCopy() {
		$errors = $this->validate();
		if( !empty($errors) && count($errors) > 0 ) {
			$this->fix();
		}
		$item = JTable::getInstance('item', 'JSpaceTable');
		$item->state = JSpaceTableItem::ITEMSTATE_UPDATED;
		$item->user_id = $this->user_id;
		$item->name = $this->name;
		$item->created = $this->created;
		$item->collectionid = $this->collectionid;
		$item->store();
		$this->updateditem_id = $item->id;
		parent::store();
		
		foreach( $this->getMetadatas() as $metadata ) {
			$item->setMetadata( $metadata->name, $metadata->value, array('archive' => $metadata->archive) );
		}
		
		foreach( $this->getBundles() as $bundle ) {
			$itemBundle = $item->getBundle( $bundle->type );
			foreach( $bundle->getBitstreams() as $bitstream ) {
				$itemBundle->cloneBitstream($bitstream);
			}
		}
		return $item;
	}
	
	/**
	 * Get the directory for storing packages before archiving.
	 */
	public function getStorageDir() {
		return JPATH_ROOT . DS . str_replace(".", DS, JComponentHelper::getParams('com_jspace')->get('storage_directory')) . DS . 'packages' . DS . $this->id . DS ;
	}
	
	/**
	 * Debug function.
	 * @return string
	 */
	public function _getDisplayState() {
		$ret = 'n/a';
		switch( $this->state ) {
			case self::ITEMSTATE_PARTIAL:	$ret = 'ITEMSTATE_PARTIAL'; break;
			case self::ITEMSTATE_SAVED:		$ret = 'ITEMSTATE_SAVED'; break;
			case self::ITEMSTATE_UPDATED:	$ret = 'ITEMSTATE_UPDATED'; break;
			case self::ITEMSTATE_INWORKFLOW:$ret = 'ITEMSTATE_INWORKFLOW'; break;
			case self::ITEMSTATE_PUBLISHED:	$ret = 'ITEMSTATE_PUBLISHED'; break;
		}
		return $ret;
	}
	
	/**
	 * Returns true if item is partial (saved by autosave when adding item) or _isNew flag is set. 
	 * @author Michał Kocztorz
	 * @return bool
	 */
	public function isNew() {
		return $this->state == self::ITEMSTATE_PARTIAL || $this->_isNew;
	}

	/**
	 * If item id a working copy (ITEMSTATE_UPDATED) return oryginal item otherwise null.
	 * @return JSpaceTableItem
	 */
	public function getOriginal() {
		if( $this->state != self::ITEMSTATE_UPDATED ) {
			return null;
		}
		
		$item = JTable::getInstance('item', 'JSpaceTable');
		$where = array('updateditem_id' => $this->id);
		if( $item->load( $where ) ) {
			return $item;
		}
		return null;
	}
	
	/**
	 * Test if is owned by passed user or if null, current user.
	 * @param JUser $JUser
	 */
	public function isOwnedBy( $JUser = null ) {
		if( is_null($JUser) ) {
			$JUser = JFactory::getUser();
		}
		return $this->user_id == $JUser->get('id');
	}
	
	/**
	 * Test if user is authorised for creating or editing this item.
	 * 
	 * @param JUser $JUser
	 * @return bool
	 */
	public function canAddOrEdit( $JUser ) {
		$authorised = false;
		if( $this->isNew() ) {
			$authorised = $JUser->authorise('core.create', 'com_jspace');
		}
		else if( $this->isOwnedBy( $user ) ) {
			$authorised = $JUser->authorise('core.edit.own', 'com_jspace') || $JUser->authorise('core.edit', 'com_jspace');
		}
		else {
			$authorised = $JUser->authorise('core.edit', 'com_jspace');
		}
		return $authorised;
	}
	
	/**
	 * Test if user is authorised to delete item.
	 * @param JUser
	 * @return bool
	 */
	public function canDelete( $JUser ) {
		$authorised = false;
		if( $this->isOwnedBy( $user ) ) {
			$authorised = $JUser->authorise('core.edit.own', 'com_jspace') || $JUser->authorise('core.delete', 'com_jspace');
		}
		else {
			$authorised = $JUser->authorise('core.delete', 'com_jspace');
		}
		return $authorised;
	}
	
	/**
	 * Get item's name. If not set return default (e.g. 'Untitled').
	 * 
	 * @return string
	 */
	public function getName() {
		$name = $this->name;
		return empty($name)? JText::_('COM_JSPACE_ITEM_UNTITLED'): $name;
	}
}
