<?php
defined('_JEXEC') or die('Restricted access');
 
jimport('joomla.form.helper');
jimport('joomla.form.field');

/**
 * A form field for adding, editing and deleting weblink references.
 */
class JSpaceFormFieldWeblinkList extends JFormField
{
	/**
	 * field type
	 * @var string
	 */
	protected $type = 'JSpace.WeblinkList';

	/**
	 * Method to get the field input markup
	 */
	protected function getInput()
	{
        $layout = JPATH_PLUGINS.'/content/jspaceweblinks/layouts';
		$html = JLayoutHelper::render("weblinklist", $this, $layout);
		return $html;
	}
	
	public function __get($name)
	{
		switch ($name) {
			case 'value':
				if (!is_array($this->$name))
				{
					$this->$name = array();
				}
				
				return $this->$name;
		
			case 'context':
				return JArrayHelper::getValue($this->element, $name, null, 'string');
				break;
			
			default:
				return parent::__get($name);
				break;
		}
	}
}