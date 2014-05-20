<?php
defined('_JEXEC') or die;

class JSpaceControllerRecord extends JControllerForm
{
	protected function allowAdd($data = array())
	{
		$user = JFactory::getUser();
		$categoryId = JArrayHelper::getValue($data, 'catid', $this->input->getInt('filter_category_id'), 'int');
		$allow = null;
	
		if ($categoryId)
		{
			// If the category has been passed in the data or URL check it.
			$allow = $user->authorise('core.create', 'com_jspace.category.' . $categoryId);
		}
	
		if ($allow === null)
		{
			// In the absense of better information, revert to the component permissions.
			return parent::allowAdd();
		}
		else
		{
			return $allow;
		}
	}
	
	public function setSchema()
	{
		$app = JFactory::getApplication();
		
		// Get the posted values from the request.
		$data = $this->input->post->get('jform', array(), 'array');
		
		// Get the type.
		$schema = $data['schema'];
		
		$schema = json_decode(base64_decode($schema));
		
		$label = isset($schema->label) ? $schema->label : null;
		$name = isset($schema->name) ? $schema->name : null;
		$recordId = isset($schema->id) ? $schema->id : 0;
		$parentId = isset($schema->parent) ? $schema->parent : 0;

		$data['schema'] = $name;
		
		$app->setUserState('com_jspace.edit.record.schema', $label);
		
		$app->setUserState('com_jspace.edit.record.data', $data);
		
		$this->setRedirect(JRoute::_('index.php?option=' . $this->option . '&view=' . $this->view_item . $this->getRedirectToItemAppend($recordId).$this->getRedirectToItemAppend($parentId, 'parent'), false));
	}
}