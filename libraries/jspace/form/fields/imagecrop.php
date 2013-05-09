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


class JSpaceFormFieldImageCrop extends JFormField
{
	/**
	 * The form field type.
	 *
	 * @var         string
	 * @since       1.6
	 */
	protected $type = 'JSpace.ImageCrop';
	
	public function getInput() {
		$document = JFactory::getDocument();
		$document->addScript(     'media/com_jspace/js/jQueryFileUpload/js/vendor/jquery.ui.widget.js'	);
		$document->addScript(     'media/com_jspace/js/jQueryFileUpload/js/jquery.iframe-transport.js'	);
		$document->addScript(     'media/com_jspace/js/jQueryFileUpload/js/jquery.fileupload.js'		);
		$document->addScript(	  'media/com_jspace/js/Jcrop/js/jquery.Jcrop.min.js'					);
		$document->addScript(	  'media/com_jspace/js/formfield/imagecrop.js'							);
		$document->addStyleSheet( 'media/com_jspace/css/formfields.css'									);
		$document->addStyleSheet( 'media/com_jspace/js/Jcrop/css/jquery.Jcrop.min.css'					);
		
		$info = JText::_('COM_JSPACE_THUMBNAIL_CROPPER_HINT');
		$remove = JText::_('COM_JSPACE_THUMBNAIL_REMOVE');
		$url = JURI::base();
		
		$html = <<< HTML
<div class="formfield-imagecrop" id="imagecrop_{$this->element['name']}">
	<input type="hidden" class="formfield-imagecrop-name" name="{$this->element['name']}[name]" value="" />			
	<input type="hidden" class="formfield-imagecrop-featured-width" name="{$this->element['name']}[featured][width]" value="" />			
	<input type="hidden" class="formfield-imagecrop-featured-height" name="{$this->element['name']}[featured][height]" value="" />			
	<input type="hidden" class="formfield-imagecrop-featured-left" name="{$this->element['name']}[featured][left]" value="" />			
	<input type="hidden" class="formfield-imagecrop-featured-top" name="{$this->element['name']}[featured][top]" value="" />			

	<input type="hidden" class="formfield-imagecrop-reference-width" name="{$this->element['name']}[reference][width]" value="" />			
	<input type="hidden" class="formfield-imagecrop-reference-height" name="{$this->element['name']}[reference][height]" value="" />			
	
	<div class="row">
		<div id="upload" class="span3">
			<input type="button" value='Upload Image' class="button btn btn-primary" />
			<input type="file" name="files[]" multiple class="fileupload" data-url="{$dataUrl}">
		</div>
		<div id="uploads" class="span3"></div>			
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
			<img  id='img' class="cropper-image" src='{$url}media/com_jspace/images/emptyImage.jpg' />
		</div>	
		<span id="status"></span>
	</div>
</div>
HTML;
		return $html;
	}
}









