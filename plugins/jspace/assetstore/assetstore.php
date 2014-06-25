<?php
defined('_JEXEC') or die;

jimport('joomla.filesystem.folder');

jimport('jspace.factory');
jimport('jspace.archive.assethelper');
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
	 * @param   JObject                   $record      An instance of the saved record.
	 * @param   bool                      $isNew       True if the record has just been created, false
	 * otherwise.
	 *
	 * @return  bool                      True if all file store requirements are met, otherwise false.
	 *
	 * @throws  UnexpectedValueException  Thrown if the file store is not writeable.
	 */
	public function onJSpaceRecordBeforeSave($record, $isNew = true)
	{
		$path = JSpaceArchiveAssetHelper::preparePath($this->params->get('path'));
		
		while ($path && !JFolder::exists($path))
		{
			$parts = explode('/', $path);
			
			array_pop($parts);
			
			$path = implode('/', $parts);
		}
		
		
		
		return true;
	}
	
	/**
	 * Saves an asset to a locally configured asset store.
	 *
	 * @param   JObject    $asset  An instance of the saved asset.
	 *
	 * @return  bool       True if the asset is successfully saved, false otherwise.
	 *
	 * @throws  Exception  Thrown if the asset cannot be saved for any reason.
	 */
	public function onJSpaceAssetAfterSave($asset)
	{
		$root = $this->get('params')->get('path', null);
		$id = $asset->id;
		
		$path = JSpaceArchiveAssetHelper::buildStoragePath($asset->record_id, $root);
		
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
	
	public function onJSpaceAssetBeforeDelete($asset)
	{
		$root = $this->get('params')->get('path', null);
		
		$storage = JSpaceArchiveAssetHelper::buildStoragePath($asset->record_id, $root);
		
		$path = $storage.$asset->hash;
		
		try
		{
			if (JFile::exists($path))
			{
				if (!JFile::delete($path))
				{
					JLog::add(__METHOD__.' '.JText::sprintf('PLG_JSPACE_ASSETSTORE_WARNING_FILEDELETEFAILED', json_encode($asset).", path=".$path), JLog::WARNING, 'jspace');
				}
			}
			else
			{
				JLog::add(__METHOD__.' '.JText::sprintf('PLG_JSPACE_ASSETSTORE_WARNING_FILEDOESNOTEXIST', json_encode($asset).", path=".$path), JLog::WARNING, 'jspace');
			}
			
			// Cleanup; try to delete as much of the path as possible.
			$empty = true;
			
			do
			{
				$array = explode('/', $storage);
				array_pop($array);
				$storage = implode('/', $array);
				
				// once we hit a directory with files or the configured archive dir, stop.
				if (JFolder::files($storage) || $storage.'/' == JSpaceArchiveAssetHelper::preparePath($root))
				{
					$empty = false;
				}
				else
				{
					JFolder::delete($storage);
				}
			}
			while ($empty);
		}
		catch (Exception $e)
		{
			JLog::add($e->getMessage(), JLog::ERROR, 'jspace');
		}
	}
}