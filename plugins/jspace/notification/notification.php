<?php
defined('_JEXEC') or die;

class PlgJSpaceNotification extends JPlugin
{
	public function onContentAfterSave($context, $dataobject, $isNew)
	{
		if ($context == 'com_jspace.dataobject')
		{
			error_log('JSpace Plugin');
			error_log(print_r($dataobject, true));
		}
	}
}