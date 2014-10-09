<?php
/**
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

// JLoader::discover('JSpaceOAIResumptionToken', __FILE__ . DIRECTORY_SEPARATOR . 'resumptiontoken');

/**
 * @author Michał Kocztorz
 * @package     JSpace
 * @subpackage  OAI
 */
abstract class JSpaceOAIResumptionToken extends JObject
{
	/**
	 * 
	 * @var array
	 */
	protected $_params = array();
	
	/**
	 * Object loaded/decoded from input.
	 * @var bool
	 */
	protected $_loaded = false;
	
	/**
	 * 
	 * @param string $type
	 * @param JInput $input
	 * 
	 * @return JSpaceOAIResumptionToken 
	 */
	public static function getInstance( $type, JInput $input ) {
		
	} 
	/**
	 * 
	 * @var JInput
	 */
	protected $_input = null;
	
	/**
	 * 
	 * @var int
	 */
	protected $_limit = 10;
	
	/**
	 * 
	 * @param JInput $input
	 */
	public function __construct( JInput $input ) {
		$this->_input = $input;
		$this->decode();
		
		$config = JSpaceFactory::getConfig();
		$this->_limit = $config->get('limit_items');
	}
	
	/**
	 * Encode token and return encoded string.
	 * 
	 * @return string
	 */
	public function encode() {
		return base64_encode( serialize($this->_params) );
	}
	
	/**
	 * Checks if input contains token and tries decoding it.
	 * If input has no token returns true.
	 * 
	 * @throws JSpaceOAIExceptionBadResumptionToken
	 */
	public function decode() {
		$token = $this->_input->get('resumptionToken', null);
		
		if( !is_null( $token ) ) {
			$this->_params = unserialize( base64_decode( $token ) );
			if( is_array($this->_params) ) {
				if( $this->_paramsValid() ) {
					$this->_loaded = true;
					return true;
				}
			}
			$this->_params = array();
			throw new JSpaceOAIExceptionBadResumptionToken();
		}
		
		return true;
	}
	
	/**
	 * Check if object loaded from input.
	 * @return boolean
	 */
	public function isLoaded() {
		return $this->_loaded;
	}
	
	abstract protected function _paramsValid();
	
	public function setParam( $key, $value ) {
		$this->_params[ $key ] = $value;
	}
	
	public function getParam( $key, $default=null ) {
		$param = JArrayHelper::getValue($this->_params, $key, $this->_input->getString($key, $default) );
		$this->setParam($key, $param);
		return $param;
	} 
	
	public function setCursor( $cursor ) {
		$this->setParam('cursor', $cursor);
	}
	
	public function getCursor() {
		return $this->getParam('cursor', 0);
	}
	
	public function moveCursor() {
		$this->setCursor( $this->getCursor() + $this->getLimit());
	}
	
	/**
	 * 
	 * @return number
	 */
	public function getLimit() {
		return $this->_limit;
	}
	
	public function setCompleteListSize( $size ) {
		$this->setParam('completeListSize', $size);
	}
	
	public function getCompleteListSize() {
		return $this->getParam('completeListSize');
	}

	
	/**
	 * Build and return next token
	 * @return JSpaceOAIResumptionToken | null
	 */
	public function nextToken() {
		if( !$this->isLoaded() ) {
			$this->setCursor( 0 );
		}
		else {
			$this->moveCursor();
		}
	}
	
	/**
	 * Add resumption token to response if needed.
	 * 
	 */
	public function addResumptionToken( SimpleXMLElement $parent ) {
		if( $this->getCursor() + $this->getLimit() < $this->getCompleteListSize() ) {
			$token = $parent->addChild( 'resumptionToken', $this->encode() );
			$token->addAttribute('completeListSize', $this->getCompleteListSize());
			$token->addAttribute('cursor', $this->getCursor());
		}
	}
	
}

