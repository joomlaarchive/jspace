<?php
/**
 * An endpoint class.
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
 * Hayden Young					<haydenyoung@wijiti.com> 
 * 
 */
 
defined('JPATH_PLATFORM') or die;

/**
 * JSpace connector endpoint class.
 *
 * @package     JSpace
 * @subpackage  Connector
 */
class JSpaceRepositoryEndpoint extends JObject
{
	/**
	 * The relative url of the REST API endpoint.
	 * @var string
	 */
	protected $url;
	
	/**
	 * An array of extra querystring variables.
	 * @var array
	 */
	protected $vars;
	
	/**
	 * An instance of the JObject class which contains values to be submitted 
	 * to the repository.
	 * @var JObject
	 */
	protected $data;
	
	/**
	 * True if the REST endpoint does not require authentication, false 
	 * otherwise.
	 * @var boolean
	 */
	protected $anonymous;
	
	/**
	 * 
	 * @var bool
	 */
	protected $cacheable = true;
	/**
	 * A group of endpoints this one belongs to.
	 * 
	 * @var string
	 */
	protected $group = 'jspace.default';
	
	/**
	 * A timeout in seconds that is should be set in request. 
	 * @var int
	 */
	protected $timeout = 10;
	
	/**
	 * Initializes an instance of the JRepositoryEndpoint class.
	 *
	 * @param string $endpoint The relative url of the REST API endpoint.
	 * @param array $vars An array of extra querystring variables.
	 * @param boolean $anonymous True if the REST endpoint does not require authentication, false
	 * @param JObject $data An instance of the JObject class which contains values to be submitted 
	 * to the repository. 
	 * otherwise.
	 */
	public function __construct($url, $vars = null, $anonymous = true, $data = null)
	{
		$this->set('url', $url);
		$this->set('vars', $vars);
		$this->set('anonymous', $anonymous);
		$this->set('data', $data);
	}
}