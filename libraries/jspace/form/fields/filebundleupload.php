<?php
/**
 * Supports a collection picker.
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
 * Micha� Kocztorz				<michalkocztorz@wijiti.com> 
 * 
 */

defined('JPATH_BASE') or die;

jimport('joomla.html.html');
jimport('joomla.form.formfield');
jimport('jspace.form.fields.multifileupload');
jimport('jspace.form.fields.jspaceservermanipulation');
jimport('saber.weblink');

JFormHelper::loadRuleClass('url');

// JHtml::addIncludePath("joomla.html.parameter.element");

class JSpaceFormFieldFileBundleUpload extends JSpaceFormFieldMultiFileUpload implements JSpaceServerManipulation
{
	/**
	 * The form field type.
	 *
	 * @var         string
	 * @since       1.6
	 */
	protected $type = 'JSpace.FileBundleUpload';
	
	/*
	 * All the links are the same, but extended by constructor with formFieldTask. This value
	 * returns as a case in switch in jspaceUpdate method so all logic is in one place. 
	 */
	protected $uploadImageURL 	= "index.php?option=com_jspace&task=submission.formFieldAction";
	protected $currentImagesURL = "index.php?option=com_jspace&task=submission.formFieldAction";
	protected $uploadLinkURL 	= "index.php?option=com_jspace&task=submission.formFieldAction";
	protected $formName = '[bitstreams]';
	
	/**
	 * 
	 * @var JSpaceTableBundle
	 */
	protected $value;
	
	/**
	 * Tell multifileupload where to look for value. It expects array of filenames.
	 * @var string
	 */
	protected $valueField = 'bitstreams';
	protected $bitstreams = array();
	
	public function setup(&$element, $value, $group = null) {
		$ret = parent::setup($element, $value, $group);
		
		$this->uploadImageURL .= "&formFieldName=" . $this->element['name'] . "&formFieldTask=addBitstream&bundle_id=" . $this->value->id;
		$this->currentImagesURL .= "&formFieldName=" . $this->element['name'] . "&formFieldTask=bundleBitstreams&bundle_id=" . $this->value->id;
		$this->uploadLinkURL .= "&formFieldName=" . $this->element['name'] . "&formFieldTask=uploadLinkURL&bundle_id=" . $this->value->id;
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see JSpaceFormFieldMultiFileUpload::_buildFormInputs()
	 */
	protected function _buildFormInputs() {
		return '<input type="hidden" name="' . $this->element['name'] . '[bundle_id]" value="' . $this->value->id . '"  />';
	}
	
	/**
	 * Build value input element that will be a part of the download template.
	 * Override.
	 */
	protected function _buildFormValueElement() {
		$input = '<input name="' . $this->element['name'] . $this->formName . '[id][]" type="hidden" value="{%=file.id%}" />';
		
		return $input;
	}
	

	/**
	 * 
	 * 
	 * @param JSpaceModelSubmission $model
	 * @param JInput $input
	 * @param string $task
	 */
	public function jspaceUpdate($model, $input, $task = 'default' ) {
		switch( $task ) {
			case 'addBitstream':
				jimport("jspace.multifileupload.BitstreamUploadHandler");
				$bundle_id = $input->get('bundle_id');
				try {
					$bundle = $model->setItemByBundleID( $bundle_id );
					$dstDir = $bundle->getPath();
					$dstUrl = $bundle->getUrl();
				
					$uploadHandler = new BitstreamUploadHandler(array(
							"bundle"		=> $bundle,
							"upload_dir"	=> $dstDir,
							"upload_url"	=> $dstUrl,
// 							"script_url"	=> JURI::base() . 'index.php?option=com_jspace&task=submission.deleteBitstream',
							"script_url"	=> JURI::base() . 'index.php?option=com_jspace&task=submission.formFieldAction&formFieldName=' . $this->element['name'] . '&formFieldTask=deleteBitstream',
							"delete_type"	=> 'POST',
					));
				}
				catch( Exception $e ) {
				
				}
				return '';
				break;
			case 'bundleBitstreams':
				$bundle_id = $input->get('bundle_id');
				$ret = array();
				try {
					$bundle = $model->setItemByBundleID( $bundle_id );
					$dstDir = $bundle->getPath();
					$dstUrl = $bundle->getUrl();
				
					$files = $bundle->getBitstreams();
					foreach( $files as $bitstream ) {
						$file = $bitstream->file;
						$filename = $bitstream->getPath();
						if( file_exists($filename) ) {
							$ret[] = array(
									"id"			=> $bitstream->id,
									"name" 			=> $file,
									"size" 			=> filesize($filename),
									"type"			=> $this->get_file_type($filename),
									"thumbnail_url" => $bitstream->getBitstreamUrl("/thumbnail/"),
// 									"delete_url" 	=> JURI::base() . 'index.php?option=com_jspace&task=submission.deleteBitstream&bitstream_id=' . $bitstream->id,
									"delete_url" 	=> JURI::base() . 'index.php?option=com_jspace&task=submission.formFieldAction&formFieldName=' . $this->element['name'] . '&formFieldTask=deleteBitstream&_method=DELETE&bitstream_id=' . $bitstream->id,
									"delete_type" 	=> "POST"
							);
						}
					}
				
				}
				catch( Exception $e ) {
						
				}
				
				return json_encode(array("files"=>$ret));
				break;
			case 'deleteBitstream':
				jimport("jspace.multifileupload.BitstreamUploadHandler");
				$bitstream_id = $input->get('bitstream_id');
				try {
					$bitstream = $model->setItemByBitstreamID( $bitstream_id );
					$uploadHandler = new BitstreamUploadHandler(array(
							"upload_dir"	=> dirname($bitstream->getPath()) . DS,
					));
					$uploadHandler->delete(false);
					$bitstream->delete();
				}
				catch(Exception $e) {
						
				}
				return '';
			case 'uploadLinkURL':
				$ret = array(
					'success' 	=> true,
					'error'		=> '',
				);
				$bundle_id = $input->get('bundle_id');
				try {
					$bundle = $model->setItemByBundleID( $bundle_id );
					$dstDir = $bundle->getPath();
					$dstUrl = $bundle->getUrl();
					
					$addLinkText = $input->get('addLinkText','','string');
					$addLinkURL = $input->get('addLinkURL','','string');
					if( empty($addLinkText) ) {
						throw new Exception(JText::_('COM_JSPACE_EXCEPTION_LINK_TEXT_EMPTY'));
					}
					
					
					$rule = new JFormRuleUrl();
					$field = array('required'=>'true');
					if( empty($addLinkURL) || !$rule->test($field, $addLinkURL) ) {
						throw new Exception(JText::_('COM_JSPACE_EXCEPTION_LINK_EMPTY'));
					}
					
					$generatedFileName=JFilterOutput::stringURLSafe($addLinkText) . '.weblink';
					
					if( !JFolder::exists($dstUrl) ) {
						JFolder::create($dstDir);
					}
					
					if( JFile::exists($dstDir . $generatedFileName) ) {
						//naming conflict, add unique id
						$generatedFileName=JFilterOutput::stringURLSafe($addLinkText) . uniqid('_') . '.weblink';
					}
					
					if( !SaberWeblink::create($addLinkText, $addLinkURL)->save($dstDir . $generatedFileName) ) {
						throw new Exception(JText::_('COM_JSPACE_EXCEPTION_CANT_CREATE_FILE'));
					}
					
					$bundle->addBitstream($dstDir . $generatedFileName);
				}
				catch( Exception $e ) {
					$ret['success'] = false;
					$ret['error'] = $e->getMessage();
				}
				return json_encode($ret);
				break;
		}
	}
	

	/**
	 * for image uploads, test for type
	 * @author Michał Kcoztorz
	 * @param unknown_type $file_path
	 * @return string
	 */
	protected function get_file_type($file_path) {
		switch (strtolower(pathinfo($file_path, PATHINFO_EXTENSION))) {
			case 'jpeg':
			case 'jpg':
				return 'image/jpeg';
			case 'png':
				return 'image/png';
			case 'gif':
				return 'image/gif';
			default:
				return '';
		}
	}
}









