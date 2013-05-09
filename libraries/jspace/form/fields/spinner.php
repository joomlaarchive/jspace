<?php
/**
 * Supports a collection picker.
 * 
 * @author		$LastChangedBy: michalkocztorz $
 * @package		JFuelUX
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


class JSpaceFormFieldSpinner extends JFormField
{
	/**
	 * The form field type.
	 *
	 * @var         string
	 * @since       1.6
	 */
	protected $type = 'JSpace.Spinner';

	public function getInput() {
		$doc = JFactory::getDocument();
		$doc->addScript('media/com_jspace/jfuelux/loader.js');
		$doc->addStyleSheet('media/com_jspace/jfuelux/css/fuelux.css');
		$doc->addScript('media/com_jspace/js/formfield/spinner.js');
		
		$class = (string)$this->element['class'];
		
		$html = <<< HTML
<div class="fuelux {$class}">
	<div class="spinner jspace-spinner" 
			data-value="{$this->spinner_value}"
			data-min="{$this->min}"
			data-max="{$this->max}"
			data-step="{$this->step}"
			data-hold="{$this->hold}"
			data-speed="{$this->speed}"
			data-disabled="{$this->disabled}"
		>
		<input type="text" name="{$this->name}" class="input-mini spinner-input" />
		<div class="spinner-buttons btn-group btn-group-vertical">
			<button class="btn spinner-up">
				<i class="icon-chevron-up"></i>
			</button>
			<button class="btn spinner-down">
				<i class="icon-chevron-down"></i>
			</button>
		</div>
	</div>
</div>
HTML;
		return $html;
	}
	
	protected $_defaults = array(
		'default_value'		=> 1,
		'min'		=> 1,
		'max'		=> 999,
		'step'		=> 1,
		'hold'		=> "true",
		'speed'		=> "medium",
		'disabled'	=> "false",
	);
	
	public function __get($name)
	{
		switch ($name)
		{
			case 'spinner_value':
				if( !empty($this->value) ) {
					return $this->value;
				}
				else if( $this->element['default_value'] ) {
					return $this->element['default_value'];
				}
				else {
					return $this->_defaults['default_value'];
				}
				break;
			case 'min':
			case 'max':
			case 'step':
			case 'hold':
			case 'speed':
			case 'disabled':
				return isset($this->element[$name])?$this->element[$name]:$this->_defaults[$name];
				break;
			default:
				return parent::__get($name);	
				break;
		}
	}
}









