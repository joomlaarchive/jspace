<?php
/**
 * A repository class.
 * Contains a business logic for particular repository type.
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

jimport('jspace.repository.dspace.item');
jimport('jspace.repository.dspace.collection');

/**
 * JSpace connector endpoint class.
 *
 * @package     JSpace
 * @subpackage  Connector
 */
class JSpaceRepositoryDspaceRepository extends JSpaceRepository
{
	/**
	 * Add item to the repository. Now JSpaceTableItem packages itself to DSpace - here it would be changed. 
	 * The package would be created in subclass to match particular archive needs.
	 * 
	 * Returns the archive item id.
	 * 
	 * @param JSpaceTableItem $storageItem
	 * @return mixed
	 */
	protected function _storeItem( $storageItem ) {
		/*
		 * Setting additional values in metadata
		 * Contributor:
		 */
		$storageItem->setMetadata("jspaceid", JFactory::getUser()->id);

		$name = JFactory::getUser()->get('name','');
		$storageItem->setMetadata("name", $name);
		
		/*
		 * Will be changed when full submission process will be done.
		 * Dates:
		 */
		$jdate = new JDate();
		$date = $jdate->format("Y-m-d\TH:i:s\Z", false);
		$storageItem->setMetadata("date_accessioned", $date);
		$storageItem->setMetadata("date_available", $date);
		$storageItem->setMetadata("date_issued", $jdate->format("Y-m-d"));
		
		
		/*
		 * Preparing and sending the package.
		 */
		$bundles = $storageItem->getBundles() ;
		$metadatas = $storageItem->getMetadatas( true ) ; //There is more data saved than we need to archive. Param=true will filter only those for archiving.
		$mapper = $this->getMapper();
		$finfo = new finfo(FILEINFO_MIME_TYPE);
		
		$xml = new JSimpleXML();
		$xml->loadString('<request></request>') ;
		$xmlCollectionId = $xml->document->addChild('collectionId');
		$xmlCollectionId->setData($storageItem->collectionid);
		$xmlMetadata = $xml->document->addChild('metadata') ;
		
		$mapper->add('title',$storageItem->name) ;
		
		foreach( $metadatas as $metadata ) {
			foreach($metadata->getValues() as $value ) {
				$mapper->add($metadata->name, $value);
			}
		}
		
		foreach( $mapper as $key => $val ) {
			if ( !empty($val) && !empty($key) ) {
				$xmlMetadataField = $xmlMetadata->addChild('field') ;
				$xmlMetadataFieldName = $xmlMetadataField->addChild('name') ;
				$xmlMetadataFieldName->setData($key) ;
				$xmlMetadataFieldValue = $xmlMetadataField->addChild('value') ;
				$xmlMetadataFieldValue->setData($val) ;
			}
		}
		
		$xmlBundles = $xml->document->addChild('bundles') ;
		
		foreach( $bundles as $bundle ) {
		
			if ( $bundle->type != JSpaceTableBundle::BUNDLETYPE_TMP_THUMBNAIL ) {
				$bitstreams = $bundle->getBitstreams() ;
		
				if ( !empty($bitstreams) ) {
					$xmlBundle = $xmlBundles->addChild('bundle') ;
					$xmlBundleName = $xmlBundle->addChild('name') ;
					$xmlBundleName->setData($bundle->type) ;
					$xmlBitstreams = $xmlBundle->addChild('bitstreams') ;
		
					foreach( $bitstreams as $k => $bitstream ) {
						$bundlePart = '';
						if( $bundle->type != JSpaceTableBundle::BUNDLETYPE_ORIGINAL ) {
							$bundlePart = '.' . JString::strtolower( $bundle->type );
						}
						$savedFileName = JFile::stripExt( $bitstream->file ) . $bundlePart . '.' . JFile::getExt( $bitstream->file );
						
						$xmlBitstream = $xmlBitstreams->addChild('bitstream') ;
						$xmlBitstreamName = $xmlBitstream->addChild('name') ;
						$xmlBitstreamName->setData( $savedFileName ) ;
						$xmlBitstreamMimetype = $xmlBitstream->addChild('mimeType') ;
						$xmlBitstreamMimetype->setData($finfo->file($bitstream->getPath())) ;
						$xmlBitstreamDescription = $xmlBitstream->addChild('description') ;
						$xmlBitstreamDescription->setData('test description') ;
		
						if ( $k == 0 ) {
							$xmlBitstreamPrimary = $xmlBitstream->addChild('primary') ;
							$xmlBitstreamPrimary->setData('true') ;
						}
						
						$files[] = array(
								'name' => $savedFileName,
								'data' => JFile::read($bitstream->getPath())
						) ;
					}
				}
			}
		}
		
		$storage_path = $storageItem->getStorageDir();
		
		if ( !JFolder::exists($storage_path) ) {
			JFolder::create($storage_path) ;
		}
		
		$xmlGenerated = str_replace(
				array('collectionid','mimetype'),
				array('collectionId','mimeType'),
				'<?xml version="1.0"?>' . $xml->document->toString()
		);
		
		$files[] = array(
				'name' => 'package.xml',
				'data' => $xmlGenerated
		) ;
		
		$package = $storage_path . 'zip.zip' ;
		$zip = JArchive::getAdapter('zip');
		
		if ($zip->create($package, $files)) {
		
			try {
				$endpoint = $this->getRestAPI()->getEndpoint('deposit', array('zip'=>"@$package"));
				$client = $this->getConnector();
				$roles = json_decode($client->post($endpoint));
				return $roles ;
			} catch (Exception $e) {
				JSpaceRepositoryError::raiseError($this, JText::_('COM_JSPACE_REPOSITORY_CANT_ARCHIVE_ITEM'));
				JSpaceRepositoryError::raiseError($this, JText::_($e->getMessage()));
				return null;
			}
		} else {
			JSpaceRepositoryError::raiseError($this, JText::sprintf('COM_JSPACE_REPOSITORY_CANT_CREATE_ZIP', JURI::current()));
			return null;
		}
	}
	
	

	/**
	 *
	 * @param mixed $id
	 * @return JSpaceRepositoryDspaceCollection
	 */
	public function dspaceGetCollection( $id ) {
		$this->flushErrors();
	
		if( !isset( $this->_collections[ $id ] ) ) {
			try {
				$this->_collections[ $id ] = new JSpaceRepositoryDspaceCollection($id, $this);
			}
			catch( Exception $e ) {
				throw JSpaceRepositoryError::raiseError( $this, $e );
			}
		}
	
		return $this->_collections[ $id ];
	}
}





