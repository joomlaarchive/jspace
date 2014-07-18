<?php
/**
 * @package     JSpace
 * @subpackage  Form
 *
 * @copyright   Copyright (C) 2014 KnowledgeARC Ltd. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */
 
defined('JPATH_PLATFORM') or die;

jimport('joomla.form.formrule');

/**
 * A rule for validating files that are uploaded through JSpace.
 *
 * @package     JSpace
 * @subpackage  Form
 */
class JFormRuleJSpaceAssets extends JFormRule
{
    public function test($element, $value, $group = null, $input = null, $form = null)
    {
		$recordId = JFactory::getApplication()->input->get('id', 0, 'int');
		
		$params = JComponentHelper::getParams('com_jspace');
		
		$uploadMaxSize = (int)($params->get('upload_maxsize', 10)) * 1024 * 1024;
		
		$collection = JSpaceHtmlAssets::getCollection();
		$bundle = JArrayHelper::getValue($collection, (string)$element->attributes()->name, array(), 'array');
		$assets = JArrayHelper::getValue($bundle, 'assets', array(), 'array');
		
		foreach ($assets as $key=>$derivative)
		{
			foreach ($derivative as $asset)
			{
				$name = JArrayHelper::getValue($asset, 'name');
				$tmp = JArrayHelper::getValue($asset, 'tmp_name');
				
                $dispatcher = JEventDispatcher::getInstance();
                JPluginHelper::importPlugin('content');
                
                try
                {
                    $result = $dispatcher->trigger('onScan', array($tmp));
				}
				catch (Exception $e)
				{
                    $element->addAttribute('message', JText::sprintf('COM_JSPACE_ERROR_VIRUSDETECTED', $name));
                    return false;
				}
				
				if (class_exists('finfo'))
				{
					$info = new finfo(FILEINFO_MIME);
					$type = $info->file($tmp);
					
					if (!$this->_isAllowedContentType($type))
					{
						$element->addAttribute('message', 
							JText::sprintf('COM_JSPACE_ERROR_WARNFILETYPENOTALLOWED', $name, $type));
						return false;
					}
				}
				
				if ($error = JArrayHelper::getValue($asset, 'error', 0, 'int'))
				{
					switch ($error)
					{
						case UPLOAD_ERR_INI_SIZE:
							$message = 'COM_JSPACE_ERROR_WARNFILETOOLARGE';
							break;
							
						case UPLOAD_ERR_FORM_SIZE:
							$message = 'COM_JSPACE_ERROR_WARNFILETOOLARGE';
							break;
							
						case UPLOAD_ERR_PARTIAL:
							$message = "The uploaded file was only partially uploaded";
							break;
							
						case UPLOAD_ERR_NO_TMP_DIR:
							$message = "Missing a temporary folder";
							break;
							
						case UPLOAD_ERR_CANT_WRITE:
							$message = "Failed to write file to disk";
							break;
							
						case UPLOAD_ERR_EXTENSION:
							$message = "File upload stopped by extension";
							break;

						default:
							$message = "Unknown upload error";
							break;
					}
					
					$element->addAttribute('message', JText::_($message));
					return false;
				}
				
				if ($uploadMaxSize != 0 && JArrayHelper::getValue($asset, 'size') > $uploadMaxSize)
				{
					$element->addAttribute('message', JText::_('COM_JSPACE_ERROR_WARNFILETOOLARGE'));
					return false;
				}

				if (!JFile::makeSafe(JArrayHelper::getValue($asset, 'name', null)))
				{
					$element->addAttribute('message', JText::_('COM_JSPACE_ERROR_NO_FILENAME'));
					return false;
				}
				
				$hash = sha1_file(JArrayHelper::getValue($asset, 'tmp_name'));
				$keys = array('hash'=>$hash,'record_id'=>$recordId);
				
				if (JSpaceAsset::getInstance()->load($keys))
				{
					JFactory::getApplication()->enqueueMessage(JText::_('COM_JSPACE_ERROR_FILE_EXISTS'), 'error');
					return false;
				}
			}
		}
		
		// check all the assets, making sure there aren't duplicates.
		if (array_key_exists((string)$element->attributes()->name, $collection))
		{
			$keys = array_keys($collection);
			$hashes = array();
			
			do
			{
				$bundle = JArrayHelper::getValue($collection, current($keys), array(), 'array');
				$assets = JArrayHelper::getValue($bundle, 'assets', array(), 'array');
				
				foreach ($assets as $dkey=>$derivative)
				{
					foreach ($derivative as $akey=>$asset)
					{
						$hash = sha1_file(JArrayHelper::getValue($asset, 'tmp_name'));

						if (array_search($hash, $hashes) === false)
						{
							$hashes[] = $hash;
						}
						else
						{
							JFactory::getApplication()->enqueueMessage(JText::sprintf('COM_JSPACE_ERROR_UPLOAD_DUPLICATE', $name), 'warning');
							return false;
						}
					}
				}
			}
			while (current($keys) != (string)$element->attributes()->name && next($keys) !== false);
		}
		
		return true;
	}
	
	private function _isAllowedContentType($contentType)
	{
		$allowed = false;
		
		$params = JComponentHelper::getParams('com_jspace');
	
		$types = explode(',', $params->get('upload_mime'));

		while ((($type = current($types)) !== false) && !$allowed)
		{
			if (JString::trim($type) && preg_match("#".$type."#i", $contentType))
			{
				$allowed = true;
			}
	
			next($types);
		}
	
		return $allowed;
	}
}