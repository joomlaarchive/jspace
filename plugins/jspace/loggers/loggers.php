<?php
/**
 * @version	$Id$
 * @copyright	Copyright (C) 2012 Wijiti Pty Ltd, Inc. All rights reserved.
 * @license	http://www.gnu.org/licenses/gpl-3.0.html
 */

// No direct access
defined('_JEXEC') or die;

jimport('joomla.plugin.plugin');

/**
 *
 * @package	JSpace.Plugin
 * @subpackage	JSpace.Init
 */
class plgJspaceLoggers extends JPlugin
{
	public function onJSpaceInitLog()
	{
		$arr = array(
			'example' => array (
					'options' => array(
							'logger'	=> 'formattedtext',
							'text_file'	=> 'jspace.example.log'
					),
					'priorities' => JLog::ALL,
					'categories' => array(JSpaceLog::CAT_REPOSITORY),
			),
		);
		return $arr;
	}
}

