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

defined('JPATH_BASE') or die;

jimport('joomla.form.formfield');
jimport('joomla.form.helper');
JFormHelper::loadFieldClass('checkbox');


class JSpaceFormFieldLicence extends JFormFieldCheckbox
{
	/**
	 * The form field type.
	 * 
	 * Checkbox has this field public, list protected...
	 *
	 * @var         string
	 * @since       1.6
	 */
	public $type = 'JSpace.Licence';
	
	public function getInput() {
		$this->element['class'] .= ' licence-checkbox';
		
		$data = 'data-error-message="'. JText::_("COM_JSPACE_CCLICENCE_ACCEPT_ERROR") .'" ';
		
		//copied because additional data param not allowed
		$class = $this->element['class'] ? ' class="' . (string) $this->element['class'] . '"' : '';
		$disabled = ((string) $this->element['disabled'] == 'true') ? ' disabled="disabled"' : '';
		$checked = ((string) $this->element['value'] == $this->value) ? ' checked="checked"' : '';
		
		// Initialize JavaScript field attributes.
		$onclick = $this->element['onclick'] ? ' onclick="' . (string) $this->element['onclick'] . '"' : '';
		
		$parent = '<input type="checkbox" name="' . $this->name . '" id="' . $this->id . '"' . ' value="'
				. htmlspecialchars((string) $this->element['value'], ENT_COMPAT, 'UTF-8') . '"' . $class . $checked . $disabled . $onclick . $data . '/>';
		
		$html = '<a rel="license" target="_blank" href="http://creativecommons.org/licenses/by-nc-sa/3.0/deed.en_US"><img alt="Creative Commons License" style="border-width:0" src="http://i.creativecommons.org/l/by-nc-sa/3.0/88x31.png" /></a><br />This work is licensed under a <a rel="license" target="_blank" href="http://creativecommons.org/licenses/by-nc-sa/3.0/deed.en_US">Creative Commons Attribution-NonCommercial-ShareAlike 3.0 Unported License</a>.';
		$html = $parent . $html;
		return $html;
	}
}









