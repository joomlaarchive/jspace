<?php
defined('_JEXEC') or die;

jimport('jspace.factory');
jimport('jspace.filesystem.file');

/**
 * Uploaded files takes the form:
 * 
 * jform[streams][fieldname]
 * 
 * or
 * 
 * jform[streams][fieldname][]
 * 
 * for multiple files.
 * 
 * Additional information such as bundles, schemas and metadata extraction tools are defined like so:
 * 
 * jform[streams][fieldname][schema]
 * jform[streams][fieldname][metadataextractionmapping]
 * jform[streams][fieldname][bundle]
 * 
 * Files which should be deleted take the form:
 * 
 * jform[streams][fieldname][delete][]
 */
class PlgJSpaceStorageNative extends JPlugin
{
	public function __construct(&$subject, $config)
	{
		parent::__construct($subject, $config);
		$this->loadLanguage();

		// load the jsolrindex component's params into plugin params for
		// easy access.
		$params = JComponentHelper::getParams('com_jspace', true);
		
		$this->params->loadArray(array('component'=>$params->toArray()));
	}
	
	public function onJSpaceFilesPrepare($data)
	{
		// if there is no id the object hasn't been created yet. Exit cleanly.
		if (!isset($data->id))
		{
			return array();
		}
		
		$path = $this->_buildStoragePath($data->id);

		$streams = array();
		
		if (JFolder::exists($path))
		{
			foreach (JFolder::folders($path, '.', false, true) as $bundle)
			{
				foreach (JFolder::files($bundle, '.', false, true) as $file)
				{					
					$streams[$file] = array('fileName'=>basename($file), 'bundle'=>basename($bundle));
				}
			}
		}

		return $streams;
	}
	
	public function onContentBeforeSave($context, $dataobject, $isNew, $ignore = false)
	{
		if ($context != 'com_jspace.dataobject' || $ignore)
		{
			return true;
		}

		$params = $this->get('params');
		
		if (($params->get('upload_maxsize', 0) * 1024 * 1024) != 0)
		{
			if (
					$_SERVER['CONTENT_LENGTH'] > ($params->get('upload_maxsize', 0) * 1024 * 1024)
					|| $_SERVER['CONTENT_LENGTH'] > (int) (ini_get('upload_max_filesize')) * 1024 * 1024
					|| $_SERVER['CONTENT_LENGTH'] > (int) (ini_get('post_max_size')) * 1024 * 1024
					|| (($_SERVER['CONTENT_LENGTH'] > (int) (ini_get('memory_limit')) * 1024 * 1024) && ((int) (ini_get('memory_limit')) != -1))
			)
			{
				JError::raiseWarning(100, JText::_('PLG_JSPACESTORAGE_NATIVE_ERROR_WARNFILETOOLARGE'));
				return false;
			}
		}
		
		// Perform basic checks on file info before attempting anything
		foreach ($this->_getUploadedFiles() as $key=>$fileGroup)
		{
			foreach ($fileGroup as $file)
			{
				$name = JArrayHelper::getValue($file, 'name');
				
				if (class_exists('finfo'))
				{
					$info = new finfo(FILEINFO_MIME);
					$tmp = JArrayHelper::getValue($file, 'tmp_name');
					$type = $info->file($tmp);
					
					if (!$this->_isAllowedContentType($type))
					{
						JError::raiseWarning(100, JText::sprintf('PLG_JSPACESTORAGE_NATIVE_ERROR_WARNFILETYPENOTALLOWED', $name, $type));
						return false;
					}
				}
				
				if (JArrayHelper::getValue($file, 'error') == 1)
				{
					JError::raiseWarning(100, JText::_('PLG_JSPACESTORAGE_NATIVE_ERROR_WARNFILETOOLARGE'));
					return false;
				}
	
				if (($params->get('upload_maxsize', 0) * 1024 * 1024) != 0 && 
					JArrayHelper::getValue($file, 'size') > ($params->get('upload_maxsize', 0) * 1024 * 1024))
				{
					JError::raiseNotice(100, JText::_('PLG_JSPACESTORAGE_NATIVE_ERROR_WARNFILETOOLARGE'));
					return false;
				}
	
				if (!JArrayHelper::getValue($file, 'name', null))
				{
					// No filename (after the name was cleaned by JFile::makeSafe)
					JError::raiseWarning(100, JText::_('PLG_JSPACESTORAGE_NATIVE_ERROR_NO_FILENAME'));
					return false;
				}
				
				$path = $this->_buildStoragePath($dataobject->id);
	
				if (JFile::exists($path.'/'.$key.'/'.JArrayHelper::getValue($file, 'name')))
				{
					// No filename (after the name was cleaned by JFile::makeSafe)
					JError::raiseWarning(100, JText::_('PLG_JSPACESTORAGE_NATIVE_ERROR_FILE_EXISTS'));
					return false;
				}
			}
		}

		return true;
	}
	
	public function onContentAfterSave($context, $dataobject, $isNew)
	{
		if ($context != 'com_jspace.dataobject')
		{
			return true;
		}

		$this->_deleteFiles($dataobject);

		$storage = $this->_buildStoragePath($dataobject->id);
		
		foreach ($this->_getUploadedFiles() as $key=>$fileGroup)
		{
			foreach ($fileGroup as $file)
			{
				$path = $storage.'/'.$key;

				if (!JFolder::create($path))
				{
					throw new Exception(JText::_("PLG_JSPACESTORAGE_NATIVE_ERROR_CREATE_STORAGE_PATH"));
				}
				
				$cleanedPath = JPath::clean($path.'/'.JArrayHelper::getValue($file, 'name'));
	
				if (!JFile::move(JArrayHelper::getValue($file, 'tmp_name'), $cleanedPath))
				{
					throw new Exception(JText::_("PLG_JSPACESTORAGE_NATIVE_ERROR_MOVE_FILE"));
				}
			}
		}

		$metadata = new JRegistry($dataobject->metadata);
		$fileInfo = array();
		
		foreach ($this->_getBundles() as $name=>$bundle)
		{
			$mapping = $this->_getMetadataExtractionMapping($name); 

			if ($mapping != 'none')
			{
				$fileInfo[$mapping] = array();
				
				foreach (JFolder::files($storage.'/'.$bundle, '.', false, true) as $file)
				{
					$cleanedPath = JPath::clean($file);
					
					$fileMetadata = JSpaceFactory::getCrosswalk(
						JSpaceFile::getMetadata($cleanedPath), array('name'=>'datastream'))->walk();
						
					$fileMetadata = new JRegistry($fileMetadata);
					
					$fileMetadata->set('checksumSHA1', sha1_file($cleanedPath));
					$fileMetadata->set('checksumMD5', md5_file($cleanedPath));
					
					if ($mapping == 'metadata')
					{
						$form = new JForm('jform');
						$form->loadFile($this->_getSchemaPath($dataobject));

						// try to match form fields to retrieved file metadata.
						foreach ($fileMetadata->toArray() as $key=>$value)
						{
							if ($form->getField($key, 'metadata'))
							{
								$fileInfo[$key][] = $value;
							}
						}
					}
					else
					{
						$fileInfo[$mapping][] = $fileMetadata->toString('ini');
					}
				}
			}
		}
		
		foreach ($fileInfo as $key=>$value)
		{
			$metadata->set($key, $value);
		}
		
		$dataobject->metadata = $metadata->toString('json');
		$dataobject->store();

		return true;
	}
	
	public function onContentAfterDelete($context, $dataobject)
	{
		if ($context != 'com_jspace.dataobject')
		{
			return true;
		}
	
		$storage = $this->_buildStoragePath($dataobject->id);
		
		try
		{
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
			$success = false;
		}
		
		return $success;
	}
	
	private function _deleteFiles($dataobject)
	{		
		$storage = $this->_buildStoragePath($dataobject->id);
		
		$form = JFactory::getApplication()->input->post->get('jform', array(), 'array');
		$stream = JArrayHelper::getValue($form, 'streams', array());

		$metadata = new JRegistry($dataobject->metadata);
		$fileInfo = array();
		
		foreach ($stream as $name=>$fileGroup)
		{
			$deletes = JArrayHelper::getValue($fileGroup, 'delete', array());
		
			foreach ($deletes as $delete)
			{
				$path = $storage.'/'.$this->_getBundle($name).'/'.$delete;
		
				if (JFile::exists($path))
				{
					// remove corresponding file metadata.
					$fileMetadata = JSpaceFactory::getCrosswalk(
						JSpaceFile::getMetadata($path), array('name'=>'datastream'))->walk();

					$fileMetadata = new JRegistry($fileMetadata);

					if ($this->_getMetadataExtractionMapping($name) == 'metadata')
					{
						$form = new JForm('jform');
						$form->loadFile($this->_getSchemaPath($dataobject));
						
						// try to match form fields to retrieved file metadata.
						foreach ($fileMetadata->toArray() as $key=>$value)
						{
							if ($form->getField($key, 'metadata'))
							{
								$fileInfo[$key][] = $value;
							}
						}
					}
					
					JFile::delete($path);
				}
			}
		}

		// remove the file info if extraction mapping is metadata.
		foreach ($fileInfo as $key=>$value)
		{
			$field = $metadata->get($key);

			if (is_array($field))
			{
				if (($index = array_search($value, $field)) !== null)
				{
					unset($field[$index]);
				}

				$metadata->set($key, $field);
			}
			else
			{
				$metadata->set($key, null);
			}
		}
	
		$dataobject->metadata = $metadata->toString('json');
		$dataobject->store();
	}
	
	private function _getSchemaPath($dataobject)
	{
		return JPATH_ROOT."/administrator/components/com_jspace/models/forms/schema.".$dataobject->schema.".xml";
	}
	
	/**
	 * Get the schema to use for creating child objects for storing files.
	 * 
	 * @param string $name The name of the file upload field.
	 * 
	 * @return string The schema to use for creating child objects for storing files. 
	 */
	private function _getSchema($name)
	{
		$form = JFactory::getApplication()->input->post->get('jform', array(), 'array');
		
		$streams = JArrayHelper::getValue(JArrayHelper::getValue($form, 'streams'), $name);
		
		return JArrayHelper::getValue($streams, 'schema', null);
	}
	
	/**
	 * Get the bundle name of a particular group of files.
	 *
	 * @param string $name The name of the file upload field.
	 *
	 * @return string The bundle name of a particular group of files.
	 */	
	private function _getBundle($name)
	{
		$form = JFactory::getApplication()->input->post->get('jform', array(), 'array');
		
		$streams = JArrayHelper::getValue(JArrayHelper::getValue($form, 'streams'), $name);
		
		return JArrayHelper::getValue($streams, 'bundle', 'original');
	}
	
	private function _getBundles()
	{
		$form = JFactory::getApplication()->input->post->get('jform', array(), 'array');
		
		$bundles = array();
		
		foreach (JArrayHelper::getValue($form, 'streams') as $key=>$value)
		{
			if ($bundle = JArrayHelper::getValue($value, 'bundle', null))
			{
				$bundles[$key] = $bundle;
			}
		}
		
		return $bundles;		
	}
	
	/**
	 * Get the mapping details for extracting metadata to.
	 * 
	 * This could be a corresponding metadata field such as "source", the special "metadata" mapping option which 
	 * is used to map the fields from the specified schema to fields in the submitted data or "none" to ignore 
	 * metadata mappings.
	 *
	 * @param string $name The name of the file upload field.
	 *
	 * @return string The mapping details for extracting metadata to.
	 */	
	private function _getMetadataExtractionMapping($name)
	{
		$form = JFactory::getApplication()->input->post->get('jform', array(), 'array');
	
		$streams = JArrayHelper::getValue(JArrayHelper::getValue($form, 'streams'), $name);

		if ($mapping = JArrayHelper::getValue($streams, 'metadataextractionmapping', 'source'))
		{
			return $mapping;
		}
		else
		{
			return 'source';
		}
	}
	
	private function _getUploadedFiles()
	{
		$form = JFactory::getApplication()->input->files->get('jform', array(), 'array');
		
		$streams = JArrayHelper::getValue($form, 'streams');
		
		$files = array();
		
		$names = array_keys($streams);

		foreach ($names as $name)
		{			
			$files[$name] = JArrayHelper::getValue($streams, $name, array());
			
			// if we are dealing with a single file upload, place in array first.
			if (!is_array(current($files[$name])))
			{
				$files[$name] = array($files[$name]);
			}

			// files don't always have to be uploaded.
			if (count($files[$name]) == 1 && JArrayHelper::getValue(JArrayHelper::getValue($files[$name], 0), 'error') == 4)
			{
				$files[$name] = array();
			}
			
			// Make file names safe.
			for ($i = 0; $i < count($files[$name]); $i++)
			{
				$files[$name][$i]['name'] = JFile::makeSafe(JArrayHelper::getValue($files[$name][$i], 'name'));
			}
		}

		return $files;
	}
	
	private function _isAllowedContentType($contentType)
	{
		$allowed = false;
	
		$types = explode(',', $this->get('params')->get('content_types_allowed'));
	
		while ((($type = current($types)) !== false) && !$allowed) {
			if (preg_match("#".$type."#i", $contentType)) {
				$allowed = true;
			}
	
			next($types);
		}
	
		return $allowed;
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
		
		$path = realpath($path);
		
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