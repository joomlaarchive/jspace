<?php
/**
 * Description of repository rest api.
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
class JSpaceRepositoryDspaceRestAPI extends JSpaceRepositoryRestAPI
{
	public function __construct() {
		$this->_endpoints = array(
			'item'	=> array(
				'url'			=> '/items/%1$s.json',
				'urlElements'	=> array(
					'id'
				),
				'vars'			=> array(),
				'anonymous'		=> true,
				'data'			=> array(),
				'group'			=> 'jspace.item.%1$s',	//for cache
				'groupElements'	=> array(
					'id'
				),
			),
			'item.bundles'	=> array(
				'url'			=> '/items/%1$s/bundles.json',
				'urlElements'	=> array(
					'id'
				),
				'vars'			=> array(),
				'anonymous'		=> true,
				'data'			=> array(),
				'group'			=> 'jspace.item.%1$s',	//the same group as item
				'groupElements'	=> array(
					'id'
				),
			),

				
				
			'communities'	=> array(
				'url'			=> '/communities.json?topLevelOnly=false',
				'urlElements'	=> array(),
				'vars'			=> array(),
				'anonymous'		=> true,
				'data'			=> array(),
				'group'			=> 'jspace.communities',
				'groupElements'	=> array(),
			),
			
				
			
			'collection'	=> array(
				'url'			=> '/collections/%1$s.json',
				'urlElements'	=> array(
					'id'
				),
				'vars'			=> array(),
				'anonymous'		=> true,
				'data'			=> array(),
				'group'			=> 'jspace.collection.%1$s',
				'groupElements'	=> array(
					'id'
				),
			),
			'collection.items'	=> array(
				'url'			=> '/collections/%1$s/items.json',
				'urlElements'	=> array(
					'id'
				),
				'vars'			=> array(
					'start'	=> true,
					'limit'	=> true,
				),
				'anonymous'		=> true,
				'data'			=> array(),
				'group'			=> 'jspace.collection.%1$s',
				'groupElements'	=> array(
					'id'
				),
			),
			'collection.countitems'	=> array(
				'url'			=> '/collections/%1$s/itemscount.json',
				'urlElements'	=> array(
					'id'
				),
				'vars'			=> array(),
				'anonymous'		=> true,
				'data'			=> array(),
				'group'			=> 'jspace.collection.%1$s',
				'groupElements'	=> array(
					'id'
				),
			),
				
				
			'discover'	=> array(
					'url'			=> 'discover.json',
					'urlElements'	=> array(),
					'vars'			=> array(
						'start'	=> true,
						'rows'	=> true,
						'q'		=> true,
						'fq'	=> true,
						'sort'	=> false,
					),
					'anonymous'		=> true,
					'data'			=> array(),
					'group'			=> 'jspace.discover',
					'groupElements'	=> array(),
			),
				
			'statistics'	=> array(
					'url'			=> 'statistics.json',
					'urlElements'	=> array(),
					'vars'			=> array(
						'facet'				=> true,
						'rows'				=> true,
						'facet.mincount'	=> true,
						'start'				=> true,
						'facet.limit'		=> true,
						'q'					=> true,
						'facet.field'		=> true,
						'fq'				=> true,
					),
					'anonymous'		=> true,
					'data'			=> array(),
					'group'			=> 'jspace.statistics',
					'groupElements'	=> array(),
			),
				
				
				
			'deposit'	=> array(
					'url'			=> 'items.stream',
					'urlElements'	=> array(),
					'vars'			=> array(),
					'anonymous'		=> false,
					'data'			=> array(
						'zip'	=> true,
					),
					'cache'			=> false,
					'timeout'		=> 180,
			),

				
			'updateitem'	=> array(
					'url'			=> 'items/%1$s/metadata.json',
					'urlElements'	=> array(
						'id'
					),
					'vars'			=> array(),
					'anonymous'		=> false,
					'data'			=> array('data'=>true), //$data will be $config['data'] (not $data['data']=$config['data'])
					'cache'			=> false,
					'timeout'		=> 180,
			),
		);
	}
}




