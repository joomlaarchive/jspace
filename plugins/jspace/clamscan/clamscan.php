<?php
/**
 * @package    JSpace.Plugin
 *
 * @copyright   Copyright (C) 2014 KnowledgeARC Ltd. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */
 
defined('_JEXEC') or die;

jimport('jspace.clamav.client');

/**
 * Scans assets using ClamAV server.
 *
 * @package  JSpace.Plugin
 */
class PlgJSpaceClamscan extends JPlugin
{
	/**
	 * Instatiates an instance of the PlgJSpaceClamscan class.
	 * @param   object  &$subject  The object to observe
	 * @param   array   $config    An optional associative array of configuration settings.
	 *                             Recognized key values include 'name', 'group', 'params', 'language'
	 *                             (this list is not meant to be comprehensive).
	 */
	public function __construct(&$subject, $config)
	{
		parent::__construct($subject, $config);
		$this->loadLanguage();
		
		JLog::addLogger(array());
		
		$params = JComponentHelper::getParams('com_jspace', true);
		
		$this->params->loadArray(array('component'=>$params->toArray()));
	}
	
	/**
	 * Scans all files.
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
					$clamav = new JSpaceClamAVClient();
					
					if ($clamav->scan(JArrayHelper::getValue($asset, 'tmp_name'), 'INSTREAM') != 'stream: OK')
					{
						JFactory::getApplication()->enqueueMessage(JText::sprintf('PLG_JSPACE_CLAMSCAN_ERROR_VIRUS_DETECTED', JArrayHelper::getValue($asset, 'name')), 'error');
						
						return false;
					}
				}
			}
		}
		
		return true;
	}
}