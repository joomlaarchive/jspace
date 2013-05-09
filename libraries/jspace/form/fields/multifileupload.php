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

jimport('joomla.form.formfield');


class JSpaceFormFieldMultiFileUpload extends JFormField
{
	/**
	 * The form field type.
	 *
	 * @var         string
	 * @since       1.6
	 */
	protected $type = 'JSpace.MultiFileUpload';
	
	protected $uploadImageURL = "index.php?option=com_jspace&task=submission.uploadImage";
	
	/**
	 * Url to retrieve already uploaded images.
	 * @var string
	 */
	protected $currentImagesURL = "index.php?option=com_jspace&task=submission.currentImages";
	
	/**
	 * Value prepared for overriding. In multifileupload file names are returned as an array of 
	 * file names. Input name is $this->element['name'], e.g name="files[]".
	 * Descendants will need to return more information, e.g. bundle id and list of filenames.
	 * File list should be returned in sub array. If this field is given a value it will
	 * be added as second level of name array, e.g. $formName="[filenames]", name for input will
	 * be name="files[filenames][]".
	 * @var string
	 */
	protected $formName = '';
	
	protected $value = array();
	/**
	 * Tell FormField where to look for value. Default it is value field.
	 * @var string
	 */
	protected $valueField = 'value';
	
	protected function _buildFormElement() {
		$delete_label = JText::_('COM_JSPACE_MULTIFILEUPLOAD_DELETE_LABEL');
		$addfiles_label = JText::_('COM_JSPACE_MULTIFILEUPLOAD_ADDFILES_LABEL');
		$cancelupload_label = JText::_('COM_JSPACE_MULTIFILEUPLOAD_CANCELUPLOAD_LABEL');
		$delete_checkbox_label = JText::_('COM_JSPACE_MULTIFILEUPLOAD_DELETEALL_LABEL');
		
		
		$input = $this->_buildFormFileInput();
		
		$html = <<< HTML
	<div class="formfield-multifileupload" id="multifileupload_{$this->element['name']}">
		<input type="hidden" name="uploading[{$this->element['name']}]" class="uploading" value="0" />
        <!-- The fileupload-buttonbar contains buttons to add/delete files and start/cancel the upload -->
        <div class="row fileupload-buttonbar">
            <div class="span5">
                <!-- The fileinput-button span is used to style the file input field as button -->
                <span class="btn btn-success fileinput-button">
                    <i class="icon-plus icon-white"></i>
                    <span>{$addfiles_label}</span>
                   	{$input}
                </span>
                <button type="button" class="btn btn-warning cancel_all">
                    <i class="icon-ban-circle icon-white"></i>
                    <span>{$cancelupload_label}</span>
                </button>
                <button type="button" class="btn btn-danger delete_selected">
                    <i class="icon-trash icon-white"></i>
                    <span>{$delete_label}</span>
                </button>
                <input type="checkbox" class="toggle_delete">
                {$delete_checkbox_label}
            </div>
            <!-- The global progress information -->
            <div class="span5 fileupload-progress fade">
                <!-- The global progress bar -->
                <div class="progress progress-success progress-striped active" role="progressbar" aria-valuemin="0" aria-valuemax="100">
                    <div class="bar" style="width:0%;"></div>
                </div>
                <!-- The extended global progress information -->
                <div class="progress-extended">&nbsp;</div>
            </div>
        </div>
        <!-- The loading indicator is shown during file processing -->
        <div class="fileupload-loading"></div>
        <br>
        <!-- The table listing the files available for upload/download -->
        <table role="presentation" class="table table-striped"><tbody class="files" data-toggle="modal-gallery" data-target="#modal-gallery"></tbody></table>
	</div>
HTML;
		return $html;
	}
	
	/**
	 * Creates the main FILE input elemrnt of the form
	 * @return string
	 */
	protected function _buildFormFileInput() {
		$data = array(
			'data-url="' . JURI::base() . $this->uploadImageURL . '"',
			'data-values="' . JURI::base() . $this->currentImagesURL . '"',
			'data-upload="template-upload-' .  $this->element['name'] . '"',
			'data-download="template-download-' .  $this->element['name'] . '"',
		);
		
		$input = '<input type="file" name="files[]" multiple class="fileupload" ' . implode(' ', $data) . '>';
		
		return $input . $this->_buildFormInputs();
	}
	
	/**
	 * Prepared for overriding. If element needs more inputs added should override this and return them (e.g. bundle id).
	 * @return string
	 */
	protected function _buildFormInputs() {
		return "";
	}
	/**
	 * Build value input element that will be a part of the download template.
	 */
	protected function _buildFormValueElement() {
		$input = '<input name="' . $this->element['name'] . $this->formName . '[]" type="hidden" value="{%=file.name%}" />';
		
		return $input;
	}
	
	public function getInput() {
		$document = JFactory::getDocument();
		$document->addStyleSheet( 'media/com_jspace/js/BootstrapImageGallery/css/bootstrap-image-gallery.min.css'	);
		
		$document->addScript(	  'media/com_jspace/js/JavaScriptTemplates/tmpl.min.js'					);
		$document->addScript(     'media/com_jspace/js/JavaScriptLoadImage/load-image.min.js'			);
		$document->addScript(     'media/com_jspace/js/JavaScriptCanvasToBlob/canvas-to-blob.min.js'	);
		$document->addScript(     'media/com_jspace/js/BootstrapImageGallery/js/bootstrap-image-gallery.min.js'	);
		$document->addScript(     'media/com_jspace/js/jQueryFileUpload/js/vendor/jquery.ui.widget.js'	);
		$document->addScript(     'media/com_jspace/js/jQueryFileUpload/js/jquery.iframe-transport.js'	);
		$document->addScript(     'media/com_jspace/js/jQueryFileUpload/js/jquery.fileupload.js'		);
		$document->addScript(     'media/com_jspace/js/jQueryFileUpload/js/jquery.fileupload-fp.js'		);
		$document->addScript(     'media/com_jspace/js/jQueryFileUpload/js/jquery.fileupload-ui.js'		);
		$document->addStyleSheet( 'media/com_jspace/js/jQueryFileUpload/css/jquery.fileupload-ui.css'	);
		$document->addScript(	  'media/com_jspace/js/formfield/multifileupload.js'					);

		$html = $this->_buildFormElement();
		
		$value = $this->{$this->valueField};
		
		$document->addScriptDeclaration("
(function($) {
	jQuery.noConflict();
	$(document).ready(function(){
		multifileupload_init('{$this->element['name']}', " . json_encode($value) . ");
	});
})(window.jQuery);
	");
		return $html . $this->_templates();
	}
	
	protected function _templates() {
		$templates = '
<script id="template-upload-' . $this->element['name'] . '" type="text/x-tmpl">
	{% for (var i=0, file; file=o.files[i]; i++) { %}
	    <tr class="template-upload fade">
	        <td class="preview"><span class="fade"></span></td>
	        <td class="name"><span>{%=file.name%}</span></td>
	        <td class="size"><span>{%=o.formatFileSize(file.size)%}</span></td>
	        {% if (file.error) { %}
	            <td class="error" colspan="2"><span class="label label-important">Error</span> {%=file.error%}</td>
	        {% } else if (o.files.valid && !i) { %}
	            <td>
	                <div class="progress progress-success progress-striped active" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"><div class="bar" style="width:0%;"></div></div>
	            </td>
	            <td class="start">{% if (!o.options.autoUpload) { %}
	                <button class="btn btn-primary">
	                    <i class="icon-upload icon-white"></i>
	                    <span>Start</span>
	                </button>
	            {% } %}</td>
	        {% } else { %}
	            <td colspan="2"></td>
	        {% } %}
	        <td class="cancel">{% if (!i) { %}
	            <button class="btn btn-warning">
	                <i class="icon-ban-circle icon-white"></i>
	                <span>' . JText::_('COM_JSPACE_MULTIFILEUPLOAD_CANCELUPLOAD_LABEL') . '</span>
	            </button>
	        {% } %}</td>
	    </tr>
	{% } %}
</script>
				
<script id="template-download-' . $this->element['name'] . '" type="text/x-tmpl"> 
		{% for (var i=0, file; file=o.files[i]; i++) { %} 
	    <tr class="template-download fade">
	        {% if (file.error) { %}
	            <td></td>
	            <td class="name"><span>{%=file.name%}</span></td> 
	            <td class="size"><span>{%=o.formatFileSize(file.size)%}</span></td> 
	            <td class="error" colspan="2"><span class="label label-important">Error</span> {%=file.error%}</td> 
	        {% } else { %}
	            <td class="preview">{% if (file.thumbnail_url) { %}
					<!-- printing src attribute instead of just typing it: Joomla SEF adds path to every src attribute breaking template --> 
	                <img {% print("src", true); %}="{%=file.thumbnail_url%}">
					' . $this->_buildFormValueElement() . '
	            {% } %}</td>
	            <td class="name"> 
	                {%=file.name%} 
	            </td>
	            <td class="size"><span>{%=o.formatFileSize(file.size)%}</span></td> 
	            <td colspan="2"></td>
	        {% } %}
	        <td class="delete"> 
	            <button class="btn btn-danger" data-type="{%=file.delete_type%}" data-url="{%=file.delete_url%}"{% if (file.delete_with_credentials) { %} data-xhr-fields=\'{"withCredentials":true}\'{% } %}> 
	                <i class="icon-trash icon-white"></i>
	                <span>' . JText::_('COM_JSPACE_MULTIFILEUPLOAD_DELETE_LABEL') . '</span> 
	            </button>
	            <input type="checkbox" name="delete" value="1"> 
	        </td>
	    </tr>
	{% } %} 
	</script>
';
		return $templates;
	}
	
	/**
	 * Return configured tmpPath.
	 * 
	 * @return String
	 */
	public function getFilePath()
	{
		$path = $this->element['tmpPath'];
		return empty($path) ? "tmp.multifileupload" : $path;
	}
}









