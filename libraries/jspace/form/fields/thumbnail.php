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
 * Michał Kocztorz				<michalkocztorz@wijiti.com> 
 * 
 */

defined('JPATH_BASE') or die;

jimport('joomla.form.formfield');
jimport('jspace.form.fields.jspaceservermanipulation');


class JSpaceFormFieldThumbnail extends JFormField implements JSpaceServerManipulation
{
	/**
	 * The form field type.
	 *
	 * @var         string
	 * @since       1.6
	 */
	protected $type = 'JSpace.Thumbnail';
	
	/**
	 * Since thumbnail is actually a set of three bundles:
	 * TMP_THUMBNAIL
	 * ITEM_THUMBNAIL
	 * ITEM_LETTERBOX
	 * 
	 * but ITEM_THUMBNAIL and ITEM_LETTERBOX are 100% dependant on TMP
	 * Field assumes working on TMP_THUMBNAIL and on form save two additional 
	 * bundles are created. 
	 * 
	 * Value of this field is JSpaceTableBundle TMP_THUMBNAIL. Other two are directly
	 * related to this one. The secondary bundles can't be edited separately. 
	 * 
	 * @var JSpaceTableBundle
	 */
	protected $value;
	
	public function getInput() {
		$document = JFactory::getDocument();
// 		$document->addScript('media/com_jspace/js/ajaxupload.3.5.js');

		$document->addScript('media/com_jspace/js/fineuploder/jquery.fineuploader-3.4.1.min.js');
// 		$document->addStyleSheet('media/com_jspace/js/fineuploder/fineuploader-3.4.1.css');
		
		$document->addScript('media/com_jspace/js/Jcrop/js/jquery.Jcrop.min.js');
		$document->addScript('media/com_jspace/js/formfield/thumbnail.js');
		$document->addStyleSheet('media/com_jspace/css/formfields.css');
		$document->addStyleSheet('media/com_jspace/js/Jcrop/css/jquery.Jcrop.min.css');
		$info = JText::_('COM_JSPACE_THUMBNAIL_CROPPER_HINT');
		$remove = JText::_('COM_JSPACE_THUMBNAIL_REMOVE');
		$wait = JText::_('COM_JSPACE_THUMBNAIL_WAIT');
		$url = JURI::base() . 'media/com_jspace/images/emptyImage.jpg';
		$bitstream = $this->value->getPrimaryBitstream();
		if( !empty($bitstream->id) ) {
			$setSelect = $bitstream->getMetadata('setSelect');
			//convert (left, top, width, height) to (x,y,x2,y2)
			$setSelect = array(
				$setSelect[0],
				$setSelect[1],
				$setSelect[0] + $setSelect[2],
				$setSelect[1] + $setSelect[3],
			);
// 			$url = $bitstream->getUrl(true);
			$url = $this->getCroppedImageUrl( $bitstream->id, true);
			$init = array(
				"url"		=> $url,
				"name"		=> $bitstream->file,
				"setSelect"	=> $setSelect 
			);
			$init = json_encode($init);
		}
		$fieldName = $this->element['name'];
		
		$html = <<< HTML
<div class="formfield-thumbnail">
	<input type="hidden" class="formfield-thumbnail-name" name="jform[{$this->element['name']}][name]" value="" />			
	<input type="hidden" class="formfield-thumbnail-featured-width" name="jform[{$this->element['name']}][featured][width]" value="" />			
	<input type="hidden" class="formfield-thumbnail-featured-height" name="jform[{$this->element['name']}][featured][height]" value="" />			
	<input type="hidden" class="formfield-thumbnail-featured-left" name="jform[{$this->element['name']}][featured][left]" value="" />			
	<input type="hidden" class="formfield-thumbnail-featured-top" name="jform[{$this->element['name']}][featured][top]" value="" />			

	<input type="hidden" class="formfield-thumbnail-reference-width" name="jform[{$this->element['name']}][reference][width]" value="" />			
	<input type="hidden" class="formfield-thumbnail-reference-height" name="jform[{$this->element['name']}][reference][height]" value="" />			
	
	<div class="row">
		<div id="upload" class="span2" data-bundle="{$this->value->id}" data-init='{$init}' data-fieldname='{$fieldName}'>
			<input type="button" id="uploadButton" value='Upload Image' class="button btn btn-primary" />
		</div>
		<div id="uploads" class="span3"><span class="wait-notice">{$wait}</span>&nbsp;</div>			
		<div class="name_file span3">			
			<button class="btn btn-warning btn-small cancel" id="remove">
				<i class="icon-ban-circle icon-white"></i>
				<span>{$remove}</span>
			</button>
		</div>
	</div>
	<div class='row '>
		<div class="span7">
			<p>{$info}</p>
			<img  id='img' class="cropper-image" src='{$url}' />
		</div>	
		<div class="span4">
			<div class="span4  img_thumbnail">
				<p>Featured Thumbnail:</p>																			
				<div id="featured-wrapper">
					<img id="featured" style='width:202px;height:125px;max-width:none !important;' src="">
				</div>
			</div>
			<div class="box_search_result span4">
				<p>Search Results</br> Thumbnail:</p>
				<div id="preview-wrapper">
					<img id="preview" style='width:125px; height:125px; max-width:none !important;' src="">
				</div>
			</div>
		</div>
		<span id="status"></span>
	</div>
</div>
HTML;
		return $html;
	}
	
	/**
	 * 
 	 * Create temp image file. Scales image down to fit in 500 x 500.
	 * 
	 * 
	 * Returns file path and url OR
	 * Error if image sent is smaller than 200 x 200.
	 * 
	 * @param JSpaceModelSubmission $model
	 * @param JInput $input
	 * @param string $task
	 */
	public function jspaceUpdate($model, $input, $task = 'default' ) {
		switch( $task ) {
			case 'default':
				$bundle_id = $input->get('bundle_id', null);
				if( !is_null( $bundle_id ) ) {
					$model->setItemByBundleID( $bundle_id );
				}
				$return = array();
				
				jimport('jspace.fineuploader.qqFileUploader');
				
				try {
					$dir = JPATH_SITE . "/tmp/thumbnaiUploads";
					if( !JFolder::exists($dir) ) {
						JFolder::create($dir);
					}
					$upload = new qqFileUploader( $dir, array('jpg','jpeg','png'), null);
					if( $upload->process() ) {
						$dest = $dir . "/" . $upload->getUploadName();
						$model->thumbImage($dest); //testing if image is ok
				
						$item = $model->getItem();
						$bitstream = $item->addThumbnail($dest);
				
		// 				$return['url'] = $bitstream->getUrl();
						$return['url'] = $this->getCroppedImageUrl($bitstream->id);
						$return['name'] = $upload->getUploadName();
				
						JFile::delete($dest);//cleanup
					}
					else {
						throw new Exception(JText::_("COM_JSPACE_ERROR_SAVING_UPLOADED_FILE"));
					}
						
					$return['success'] = true;
				}
				catch(Exception $e) {
					// Fatal error: Call to a member function getMessage() on a non-object in C:\wamp\www\jspace\Joomla\development\site\components\com_jspace\controllers\submission.php on line 149
					// $return['error'] = $e->getMessage() . ' ' . $upload->getMessage();
					$return['error'] = $e->getMessage() ;
				}
				return json_encode($return);
				break;
			case 'croppedImage':
				jimport('jspace.image.image');
				$bitstream_id = $input->get('bitstream_id');
				try {
					$bitstream = $model->setItemByBitstreamID( $bitstream_id );
					$path = $bitstream->getPath();
					$image = new JSpaceImage();
					$image->loadFile($path);
					$image->resize(500, 500, false, JImage::SCALE_INSIDE);
						
					header('Content-Type: image/jpg');
					$image->outputJpg();
				}
				catch(Exception $e) {
				
				}
				
				return '';
				break;
			case 'deleteItemThumbnail':
				$bundle_id = $input->get('bundle_id', null);
				if( !is_null($bundle_id) ) {
					$model->setItemByBundleID( $bundle_id );
				}
				$model->deleteItemThumbnail();
				return '';
				break;
		}
	}
	
	
	/**
	 *
	 * @param int $bitstream_id
	 * @param bool $avoidCache
	 * @return string
	 */
	public function getCroppedImageUrl($bitstream_id, $avoidCache=false) {
		$uniqid = '';
		if( $avoidCache ) {
			$uniqid = '&uniqid=' . uniqid();
		}
		$url = JURI::root() . 'index.php?option=com_jspace&task=submission.formFieldAction&formFieldName=' . $this->element['name'] . '&formFieldTask=croppedImage&bitstream_id=' . $bitstream_id . $uniqid;
		return $url;
	}
	
}









