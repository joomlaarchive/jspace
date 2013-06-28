<?php
/**
 * A repository error class.
 * 
 * @package		JSpace
 * @subpackage	Repository
 * @copyright	Copyright (C) 2012 Wijiti Pty Ltd. All rights reserved.
 * @license     This file is part of the JSpace library for Joomla!.

   The JSpace library for Joomla! is free software: you can redistribute it 
   and/or modify it under the terms of the GNU General Public License as 
   published by the Free Software Foundation, either version 3 of the License, 
   or (at your option) any later version.

   The JSpace library for Joomla! is distributed in the hope that it will be 
   useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.

   You should have received a copy of the GNU General Public License
   along with the JSpace library for Joomla!.  If not, see 
   <http://www.gnu.org/licenses/>.

 * Contributors
 * Please feel free to add your name and email (optional) here if you have 
 * contributed any source code changes.
 * Name							Email
 * Michał Kocztorz				<michalkocztorz@wijiti.com> 
 * 
 */
 
defined('JPATH_PLATFORM') or die;


/**
 * @package     JSpace
 * @subpackage  Repository
 */
abstract class JSpaceRepositoryError extends JObject
{
	const LEVEL_WARNING = 1;
	const LEVEL_ERROR = 2;
	const LEVEL_FATAL = 3;
	
	public $code = 500;
	public $message = 'OK';
	public $level = self::LEVEL_ERROR;
	
	public function __construct( $msg, $code=500, $level=self::LEVEL_ERROR ) {
		$this->message = $msg;
		$this->code = $code;
		$this->level - $level;
	}
	
	/**
	 * 
	 * @param JObject $obj
	 * @param mixed $message
	 */
	public static function raiseError( $obj, $message, $code=500 ) {
		if( is_object($message) ) {
			//message is exception objects
			$msg = $message->getMessage();
			$exception = $message;
		}
		else {
			$msg = $message;
			$exception = new JException($message, $code);
		}
		$obj->setError( $msg );
		JSpaceLog::add( $msg, JLog::DEBUG, JSpaceLog::CAT_ERROR );
		
		return $exception;
	}
}




