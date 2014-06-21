<?php
defined('_JEXEC') or die;

jimport('joomla.filesystem.folder');

jimport('jspace.factory');
jimport('jspace.filesystem.file');
jimport('jspace.html.assets');

/**
 * Uploaded assets takes the form:
 * 
 * jform['collection'][fieldname]['assets'][derivative]
 * 
 * or
 * 
 * jform['collection'][fieldname]['assets'][derivative][]
 * 
 * for multiple files.
 * 
 * Additional information such as schemas are defined like so:
 * 
 * jform['collection'][fieldname][schema]
 */
class PlgJSpaceAssetstore extends JPlugin
{
	public function __construct(&$subject, $config)
	{
		parent::__construct($subject, $config);
		$this->loadLanguage();
		
		JLog::addLogger(array());

		// load the jsolrindex component's params into plugin params for
		// easy access.
		$params = JComponentHelper::getParams('com_jspace', true);
		
		$this->params->loadArray(array('component'=>$params->toArray()));
	}
	
	/**
	 * Checks for the existence of a similar file already archived against the current record.
	 *
	 * @param  JForm  $form
	 * @param  array  $data
	 * @param  array  $group
	 */
	public function onJSpaceRecordAfterValidate($form, $data, $group = null)
	{
		$collection = JSpaceHtmlAssets::getCollection();
		
		foreach ($collection as $bkey=>$bundle)
		{
			$assets = JArrayHelper::getValue($bundle, 'assets', array(), 'array');
		
			foreach ($assets as $dkey=>$derivative)
			{
				foreach ($derivative as $akey=>$asset)
				{
					$hash = sha1_file(JArrayHelper::getValue($asset, 'tmp_name'));
				
					$database = JFactory::getDbo();
					$query = $database->getQuery(true);
					$query
						->select('id')
						->from('#__jspace_assets')
						->where(array(
							$database->qn('hash')."=".$database->q($hash),
							$database->qn('record_id')."=".JArrayHelper::getValue($data, 'id', 0, 'int')));
					
					$database->setQuery($query);
					
					if ($database->loadResult())
					{
						JFactory::getApplication()->enqueueMessage(JText::_('COM_JSPACE_ERROR_FILE_EXISTS'), 'error');
						return false;
					}
				}
			}
		}
		
		return true;
	}

	/**
	 * Validates asset store location before the record is saved.
	 *
	 * @param   JObject    $record      An instance of the saved record.
	 * @param   bool       $isNew       True if the record has just been created, false otherwise.
	 *
	 * @return  bool       True if all file store requirements are met, otherwise false.
	 *
	 * @throws  Exception  Thrown if the file store is not writeable.
	 */
	public function onJSpaceRecordBeforeSave($record, $isNew = true)
	{
		$path = $this->_getPath();
		
		while ($path && !JFolder::exists($path))
		{
			$parts = explode('/', $path);
			
			array_pop($parts);
			
			$path = implode('/', $parts);
		}
		
		if (!is_writeable($path))
		{
			throw new Exception(JText::_('PLG_JSPACE_ASSETSTORE_ERROR_SAVE_NOT_WRITEABLE'));
		}
		
		return true;
	}
	
	/**
	 * Saves an asset to a locally configured file store.
	 *
	 * @param   JObject    $asset  An instance of the saved asset.
	 *
	 * @return  bool       True if the asset is successfully saved, false otherwise.
	 *
	 * @throws  Exception  Thrown if the file cannot be saved for any reason.
	 */
	public function onJSpaceAssetAfterSave($asset)
	{
		$path = $this->_buildStoragePath($asset->record_id).'/';
					
		if (!JFolder::create($path))
		{
			throw new Exception(JText::_("PLG_JSPACE_ASSETSTORE_ERROR_CREATE_STORAGE_PATH"));
		}
		
		if (!JFile::copy($asset->tmp_name, $path.$asset->hash))
		{
			throw new Exception(JText::_("PLG_JSPACE_ASSETSTORE_ERROR_MOVE_FILE"));
		}
		
		return true;
	}
	
	/**
	 * Removes all assets and files on the filesystem before the record is deleted.
	 * @param   JTable  $record  An instance of the JTableRecord table.
	 *
	 * @return  bool  True if the files are removed successfully, false otherwise.
	 */
	public function onJSpaceRecordBeforeDelete($record)
	{
		$id = $record->id;
		
		$storage = $this->_buildStoragePath($id);
		
		try
		{
			$database = JFactory::getDbo();
			
			$query = $database->getQuery(true);
			$query
				->delete('#__jspace_assets')
				->where($database->qn('record_id').'='.(int)$id);
			
			$database->setQuery($query);
			$database->execute();
			
			if ($success = JFolder::delete($storage))
			{
				$empty = true;
				
				do
				{
					$array = explode('/', $storage);
					array_pop($array);
					$storage = implode('/', $array);
				
					// once we hit a directory with files or the configured archive dir, stop.
					if (JFolder::files($storage) || $storage.'/' == $this->_getPath())
					{
						$empty = false;
					}
					else
					{
						$success = JFolder::delete($storage);
					}
				}
				while ($empty);
			}
		}
		catch (Exception $e)
		{
			JLog::add($e->getMessage(), JLog::ERROR, 'jspace');
			
			$success = false;
		}
		
		return $success;
	}
	
	public function onJSpaceAssetBeforeDelete($asset)
	{
		$storage = $this->_buildStoragePath($asset->record_id);
		
		$metadata = new JRegistry($asset->metadata);
		
		$path = $storage.'/'.$asset->hash;
		
		if (JFile::exists($path))
		{
			// only the files are deleted. The path will remain even if there are no files.
			if (!JFile::delete($path))
			{
				throw new Exception(JText::_('PLG_JSPACE_ASSETSTORE_EXCEPTION_FILEDELETEFAILED'));
			}
		}
		else
		{
			JLog::add('PlgJSpaceAssetStore::onJSpaceAssetBeforeDelete; '.JText::sprintf('PLG_JSPACE_ASSETSTORE_WARNING_FILEDELETEDOESNOTEXIST', json_encode($asset)), JLog::ERROR, 'jspace');
		}
	}

	private function _buildStoragePath($id)
	{
		$hashcode = self::_getHashCode((string)$id);
		
        $mask = 255;
        
        $parts = array();

        $parts[] = str_pad(($hashcode & $mask), 3, '0', STR_PAD_LEFT);

        $parts[] = str_pad((($hashcode >> 8) & $mask), 3, '0', STR_PAD_LEFT);

        $parts[] = str_pad((($hashcode >> 16) & $mask), 3, '0', STR_PAD_LEFT);

		return $this->_getPath().implode("/", $parts);
	}
	
	private function _getPath()
	{
		$path = $this->get('params')->get('path', null);
		
		if (strpos($path, 'JPATH_ROOT') === 0)
		{
			$path = str_replace('JPATH_ROOT', JPATH_ROOT, $path);
		}
		
		if (strpos(strrev($path), '/') !== 0)
		{
			$path .= '/';
		}
		
		return $path;
	}

	private static function _getHashCode($s)
	{
		$h = 0;
		$len = strlen($s);
		for($i = 0; $i < $len; $i++)
		{
			$h = (int)(31 * $h + ord($s[$i])) & 0xffffffff;
		}

		return $h;
	}
}