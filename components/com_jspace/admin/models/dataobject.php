<?php
defined('_JEXEC') or die;

class JSpaceModelDataObject extends JModelAdmin
{
	protected $context;

	public function __construct($config = array())
	{
		parent::__construct($config);
		
		$this->context = $this->get('option').'.'.$this->getName();
	}
	
	public function getItem($pk = null)
	{
		$app = JFactory::getApplication();
		
		if ($item = parent::getItem($pk))
		{
			// Convert the metadata field to an array.
			$registry = new JRegistry;
			$registry->loadString($item->metadata);
			$item->metadata = $registry->toArray();	
		}

		// Load associated content items
		$assoc = JLanguageAssociations::isEnabled();
	
		if ($assoc)
		{
			$item->associations = array();
	
			if ($item->id != null)
			{
				$associations = JLanguageAssociations::getAssociations($this->option, '#__jspace_dataobjects', $this->context, $item->id, 'id', null, null);
	
				foreach ($associations as $tag => $association)
				{
					$item->associations[$tag] = $association->id;
				}
			}
		}
		
		if (!($parent = JFactory::getApplication()->input->getInt('parent'))) {
			$parent = $item->parent_id;
		}
		
		if ($parent)
		{
			$table = JTable::getInstance('DataObject', 'JSpaceTable');
			$table->load($parent);
			$item->parentTitle = $table->title;
		}
		
		// Override the base user data with any data in the session.
		$data = $app->getUserState('com_jspace.edit.dataobject.data', array());
		foreach ($data as $k => $v)
		{
			$item->$k = $v;
		}
		
		$dispatcher = JEventDispatcher::getInstance();
		JPluginHelper::importPlugin('jspacestorage');
		JPluginHelper::importPlugin('jspace');
		
		// Trigger the data preparation event.
		$dispatcher->trigger('onContentPrepareData', array($this->context, $item));

		return $item;
	}
	
	public function getForm($data = array(), $loadData = true)
	{
		$form = $this->loadForm($this->context, $this->getName(), array('control'=>'jform', 'load_data'=>$loadData));

		if (empty($form))
		{
			return false;
		}

		// only grab the data from the form if it is being loaded.
		if ($loadData) {
			$data = $form->getData()->toArray();
		}

		// if the parent id is not in the querystring, try to get it from the submitted data.
		if (!($parent = JFactory::getApplication()->input->getInt('parent'))) {
			$parent = JArrayHelper::getValue($data, 'parent_id');
		}

		// show the parent if it is specified, otherwise provide access to the
		// category.
		if ($parent)
		{
			$form->removeField('catid');
			$form->setValue('parent_id', null, $parent);
		}
		else
		{
			$form->removeField('parent_id');
			$form->removeField('parentTitle');
		}

		return $form;
	}

	protected function loadFormData()
	{
		// Check the session for previously entered form data.
		$app = JFactory::getApplication();

		$data = $this->getItem();

		$this->preprocessData($this->context, $data);

		return $data;
	}

	protected function preprocessForm(JForm $form, $data, $group = 'content')
	{
		// force to array (perhaps move to $this->loadFormData())
		$data = (array)$data;	

		// try to get the schema from the posted data if it isn't in $data.
		if (!($schema = JArrayHelper::getValue($data, 'schema')))
		{
			$tmp = JFactory::getApplication()->input->post->get('jform', array(), 'array');
			
			$schema = JArrayHelper::getValue($tmp, 'schema');
		}

		if ($schema) 
		{
			$path = JPATH_ROOT.'/administrator/components/com_jspace/models/forms/schema.'.$schema.'.xml';			
			$form->loadFile($path, false);
		}
		
		$assoc = JLanguageAssociations::isEnabled();
		if ($assoc)
		{
			$languages = JLanguageHelper::getLanguages('lang_code');
				
			$addform = new SimpleXMLElement('<form />');
			$fields = $addform->addChild('fields');
			$fields->addAttribute('name', 'associations');
			$fieldset = $fields->addChild('fieldset');
			$fieldset->addAttribute('name', 'item_associations');
			$fieldset->addAttribute('description', 'COM_JSPACE_DATAOBJECT_ASSOCIATIONS_FIELDSET_DESC');
			$add = false;
				
			foreach ($languages as $tag => $language)
			{
				if (empty($data['language']) || $tag != $data['language'])
				{
					$add = true;
					$field = $fieldset->addChild('field');
					$field->addAttribute('name', $tag);
					$field->addAttribute('type', 'JSpace.DataObject');
					$field->addAttribute('language', $tag);
					$field->addAttribute('label', $language->title);
					$field->addAttribute('translate_label', 'false');
					$field->addAttribute('edit', 'true');
					$field->addAttribute('clear', 'true');
				}
			}
				
			if ($add)
			{
				$form->load($addform, false);
			}
		}

		parent::preprocessForm($form, $data, $group);
	}

	public function getTable($type = 'DataObject', $prefix = 'JSpaceTable', $config = array())
	{
		return JTable::getInstance($type, $prefix, $config);
	}
	
	public function prepareTable($table)
	{
		// Set the publish date to now
		$db = $this->getDbo();
		if ($table->state == 1 && (int) $table->publish_up == 0)
		{
			$table->publish_up = JFactory::getDate()->toSql();
		}
		
		if ($table->state == 1 && intval($table->publish_down) == 0)
		{
			$table->publish_down = $db->getNullDate();
		}
		
		// Increment the content version number.
		$table->version++;
	}

	public function save($data)
	{
		$dispatcher = JEventDispatcher::getInstance();
		
		JPluginHelper::importPlugin('content');
		JPluginHelper::importPlugin('jspace');
		JPluginHelper::importPlugin('jspacestorage');
		
		$table = $this->getTable();
	
		if ((!empty($data['tags']) && $data['tags'][0] != ''))
		{
			$table->newTags = $data['tags'];
		}
	
		$key = $table->getKeyName();
		$pk = (!empty($data[$key])) ? $data[$key] : (int) $this->getState($this->getName() . '.id');
		$isNew = true;
	
		// Allow an exception to be thrown.
		try
		{
			// Load the row if saving an existing record.
			if ($pk > 0)
			{
				$table->load($pk);
				$isNew = false;
			}
	
			// Bind the data.
			if (!$table->bind($data))
			{
				$this->setError($table->getError());
	
				return false;
			}
	
			// Prepare the row for saving
			$this->prepareTable($table);
	
			// Check the data.
			if (!$table->check())
			{
				$this->setError($table->getError());
				return false;
			}
	
			// Trigger the onContentBeforeSave event.
			$result = $dispatcher->trigger($this->event_before_save, array($this->option . '.' . $this->name, $table, $isNew));
	
			if (in_array(false, $result, true))
			{
				$this->setError($table->getError());
				return false;
			}
	
			// Store the data.
			if (!$table->store())
			{
				$this->setError($table->getError());
				return false;
			}
	
			// Clean the cache.
			$this->cleanCache();
				
			if ($table->$key)
			{
				if ($catid = JArrayHelper::getValue($data, 'catid'))
				{
					$dataobjectCategory = $this->getTable('DataObjectCategory');
					$dataobjectCategory->dataobject_id = $table->$key;
					$dataobjectCategory->catid = $catid;
					$dataobjectCategory->store();
				}
			}
	
			// Trigger the onContentAfterSave event.
			$dispatcher->trigger($this->event_after_save, array($this->option . '.' . $this->name, $table, $isNew));
		}
		catch (Exception $e)
		{
			$this->setError($e->getMessage());
	
			return false;
		}

		if (isset($table->$key))
		{
			$this->setState($this->getName() . '.id', $table->$key);
		}
	
		$this->setState($this->getName() . '.new', $isNew);
	
		return true;
	}
	
	public function delete(&$pks)
	{
		$dispatcher = JEventDispatcher::getInstance();

		JPluginHelper::importPlugin('jspace');
		JPluginHelper::importPlugin('jspacestorage');
		
		return parent::delete($pks);
	}
}