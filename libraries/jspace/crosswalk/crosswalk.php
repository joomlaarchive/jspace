<?php
/**
 *
 * @author		$LastChangedBy: michalkocztorz $
 * @package		JSpace
 * @copyright	Copyright (C) 2011 Wijiti Pty Ltd. All rights reserved.
 * @license     This file is part of the JSpace component for Joomla!.

 The JSpace component for Joomla! is free software: you can redistribute it
 and/or modify it under the terms of the GNU General Public License as
 published by the Free Software Foundation, either version 3 of the License,
 or (at your option) any later version.

 The JSpace component for Joomla! is distributed in the hope that it will be
 useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with the JSpace component for Joomla!.  If not, see
 <http://www.gnu.org/licenses/>.

 * Contributors
 * Please feel free to add your name and email (optional) here if you have
 * contributed any source code changes.
 * Name							Email
 * Michał Kocztorz				<michalkocztorz@wijiti.com>
 *
 */

defined('JPATH_PLATFORM') or die;

/*
 * Sample use.
 * What we will encapsulate here is another story. Will this be config, translation class, or anything else...
 * 
 * all not tested, just an idea
 */
/*
j
import('jspace.crosswalk.crosswalk');
$cw = JSpaceCrosswalk::factory('dublin core');
$author = $cw->_('author');

Note:
create a mapper class that will take care of naming conflicts. Class after feeding with data like
$mapper->set('author', $author)
$mapper->set('author', $ilustrator)

eventually this:
$mapper->set($cw->_('author'), $ilustrator)

should act as array

*/

class JSpaceCrosswalk {
	protected static $crosswalks = array();
		
	/**
	 * 
	 * @author Michał Kocztorz
	 * @param string $type
	 * @return JSpaceCrosswalk
	 */
	public static function factory( $type ) {
		if( !isset(self::$crosswalks[ $type ]) ) {
			$class = "JSpace" . ucfirst($type) . "Crosswalk"; //look for JSpace<Type>Crosswalk class
			if( !class_exists($class) ) {
				//attempt import
				jimport('jspace.crosswalk.' . strtolower($type) . '.crosswalk');
				if( !class_exists($class) ) {
					$class = 'JSpaceCrosswalk'; //use the default one if JSpace<Type>Crosswalk is not found
				}
			}
			self::$crosswalks[ $type ] = new $class( strtolower($type) );
		}
		return self::$crosswalks[ $type ];
	}
	
	/**
	 * 
	 * @var array
	 */
	protected $map = array();
	
	/**
	 * 
	 * @var JRegistry
	 */
	protected $_registry = null;
	
	/**
	 * 
	 * @var string
	 */
	protected $_format = 'JSON';
	
	/**
	 * 
	 * @var string
	 */
	protected $_file = null;
	
	/**
	 * 
	 * @var array
	 */
	protected $_options = array(); 
	
	/**
	 * 
	 * @var string
	 */
	protected $_type = '';
	
	/**
	 * 
	 */
	public function __construct( $type ) {
		$this->_type = $type;
		$this->_setDefaultFile();
		$this->_loadRegistry();
	}
	
	/**
	 * This method should be overriden in subclass to change the _file 
	 * 
	 */
	protected function _setDefaultFile() {
		$this->_file = dirname(__FILE__) . "/" . $this->_type . "/crosswalk"; 
	}
	
	/**
	 * This method may be overriden in subclass to load the registry from other source than file. 
	 * 
	 */
	protected function _loadRegistry() {
		$this->_registry = new JRegistry();
		$formats = array(
				array( 'format' => $this->_format, 	'ext' => ''),		//first test current class settings
				array( 'format' => 'JSON', 			'ext' => '.json' ), 
				array( 'format' => 'INI', 			'ext' => '.ini' ),
				array( 'format' => 'XML', 			'ext' => '.xml' ),
				array( 'format' => 'PHP', 			'ext' => '.php' ) 
		);
		$oryginalFile = $this->_file;
		$loaded = false;
		foreach( $formats as $row ) {
			$format = $row['format'];
			$fileExtension = $row['ext'];
			if( !$loaded ) {
				$this->_file = $oryginalFile . $fileExtension;
				$this->_format = $format;
				/*
				 * JRegistry loadFile will not fail if file is not well formatted.
				 */
				if( JFile::exists($this->_file) && $this->_registry->loadFile( $this->_file, $this->_format, $this->_options ) === true ) {
					$loaded = true;
				}
			}
		}
		if( !$loaded ) {
			throw new Exception( JText::sprintf('COM_JSPACE_MISSING_CROSSWALK_CONFIGURATION', get_class($this)) );
		}
		
		$this->map = $this->_registry->toArray();
	}

	
	/**
	 * 
	 * @param string $key
	 * @return Ambigous <bool, string >
	 */
	public function _( $key ) {
		/*
		 * ToDo: if config setting is set to add unmapped metadata, add it ? 
		 */
		return isset( $this->map[ $key ] ) ? $this->map[ $key ] : false;
	}
	
	/**
	 * 
	 * @param string $key
	 * @param string $val
	 */
	protected function _addKey( $key, $val ) {
		$this->map[ $key ] = $val;
	}
	
	/**
	 * Returns: 
	 * - false or string when $returnOnlyFirst = true
	 * - array (may be empty) when $returnOnlyFirst = false
	 * 
	 * @param string $value
	 * @param bool $returnOnlyFirst
	 * @return mixed|multitype:
	 */
	public function getKey( $value, $returnOnlyFirst = true ) {
		$config = JSpaceFactory::getConfig();
		if( $returnOnlyFirst ) {
			$ret = array_search( $value, $this->map );
			if( $ret === false ) {
				if( $config->get('show_unmapped_metadata', false) ) {
					$this->_addKey($value, $value); //adding a pair of the same key->value
					$ret = $value;
				}
			}
			return $ret;
		}
		else {
			$ret = array_keys( $this->map, $value);
			if( count($ret) == 0 ) {
				if( $config->get('show_unmapped_metadata', false) ) {
					$this->_addKey($value, $value);
					$ret = array($value);
				}
			}
			return $ret;
		}
	}
	
	/**
	 * Get crosswalk type.
	 * 
	 * @return string
	 */
	public function getType() {
		return $this->_type;
	}
}