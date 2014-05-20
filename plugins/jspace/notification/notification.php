<?php
defined('_JEXEC') or die;

class PlgJSpaceNotification extends JPlugin
{
	public function onContentAfterSave($context, $record, $isNew)
	{
		if ($context == 'com_jspace.record')
		{
			error_log('JSpace Plugin');
			error_log(print_r($record, true));
		}
	}
}