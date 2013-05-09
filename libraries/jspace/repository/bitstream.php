<?php
/**
 * Bitstream class. Bitstream is a file in archive (part of bundle).
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
 * @author Michał Kocztorz
 * @package     JSpace
 * @subpackage  Repository
 */
abstract class JSpaceRepositoryBitstream extends JObject
{
	/**
	 * @var JSpaceRepositoryBundle
	 */
	protected $_bundle = null;
	
	/**
	 * Unique identifier for bitstream.
	 * @var mixed
	 */
	protected $_id = null;

	/**
	 * Construct object using parent bundle and unique identifier of this bitstream.
	 * The driver-specyfic inicialisation is done in _init() method that must be defined in subclass.
	 * @param JSpaceRepositoryBundle $bundle
	 */
	public function __construct( $bundle, $id ) {
		$this->_bundle = $bundle;
		$this->_id = $id;
		$this->_init();
	}
	
	/**
	 * Return the parent bundle object.
	 * 
	 * @return JSpaceRepositoryBundle
	 */
	public function getBundle() {
		return $this->_bundle;
	}
	
	/**
	 * Return the uique identifier of this bitstream.
	 * @return mixed
	 */
	public function getId() {
		return $this->_id;
	}
	
	/**
	 * Subclass should use this method to initialize driver-specyfic data.
	 * 
	 * @return null
	 */
	abstract protected function _init();
	
	/**
	 * Get bitstream url.
	 * 
	 * @return string
	 */
	abstract public function getUrl();

	abstract public function getName();

	abstract public function getDescription();

	abstract public function getSize();

	abstract public function getFormatDescription();
}




