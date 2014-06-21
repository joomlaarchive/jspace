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
				
				if (class_exists('finfo'))
				{
					$info = new finfo(FILEINFO_MIME);
					$tmp = JArrayHelper::getValue($asset, 'tmp_name');
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
			}
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