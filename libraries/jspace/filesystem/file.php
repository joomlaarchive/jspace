<?php
defined('_JEXEC') or die;

jimport('joomla.filesystem.file');

class JSpaceFile extends JFile
{
	public function getMetadata($file)
	{
		$params = JComponentHelper::getParams('com_jspace', true);
		
		if (!$params)
		{
			throw new Exception("LIB_JSPACE_COM_JSPACE_NOT_FOUND");
		}
		
		if (!$params->get('local_tika_app_path', null))
		{
			throw new Exception("LIB_JSPACE_TIKA_NOT_FOUND");
		}

		ob_start();		
		passthru("java -jar ".$params->get('local_tika_app_path')." -j \"".$file."\" 2> /dev/null");
		$result = ob_get_contents();
		ob_end_clean();
		
		$metadata = new JRegistry();
		$metadata->loadString($result);

		return $metadata;
	}
}