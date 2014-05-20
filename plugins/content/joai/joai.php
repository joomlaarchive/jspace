<?php
defined('JPATH_BASE') or die;

class PlgContentJOAI extends JPlugin
{
	/**
	 *
	 */
	public function __construct($subject, $config = array())
	{	
		parent::__construct($subject, $config);
		$this->loadLanguage();
	}

	public function onContentPrepareData($context, $data)
	{
		if ($context != 'com_categories.category')
		{
			return true;
		}
		
		if (is_object($data))
		{
			if ($data->extension != 'com_jspace')
			{
				return true;
			}
			
			$database = JFactory::getDbo();
			$query = $database->getQuery(true);
			
			$query
				->select(array($database->qn('url'), $database->qn('metadataFormat')))
				->from($database->qn('#__joai_harvests', 'h'))
				->where($database->qn('catid')."=".(int)$data->id);
				
			$database->setQuery($query);
			
			$result = $database->loadObject();
			
			$data->joai['url'] = $result->url;
		}

		return true;
	}
	
	public function onContentPrepareForm($form, $data)
	{
		if (!($form instanceof JForm))
		{
			$this->_subject->setError('JERROR_NOT_A_FORM');
			return false;
		}

		$name = $form->getName();

		if (!in_array($name, array('com_categories.categorycom_jspace')))
		{
			return true;
		}

		JForm::addFormPath(__DIR__.'/forms');
		$form->loadFile('oai', false);
		return true;
	}
	
	public function onContentAfterSave($context, $category, $isNew)
	{
		if ($context != 'com_categories.category')
		{
			return true;
		}
		
		$data = JFactory::getApplication()->input->post->get('jform', array(), 'array');
		
		if ($category->extension == 'com_jspace')
		{
			$oai = JArrayHelper::getValue($data, 'joai');
			$url = JArrayHelper::getValue($oai, 'url');
			$metadataFormat = JArrayHelper::getValue($oai, 'metadataFormat');
			
			$this->onContentAfterDelete($context, $category);
		
			$database = JFactory::getDbo();
			$query = $database->getQuery(true);
			
			$columns = array(
				$database->qn('catid'),
				$database->qn('url'),
				$database->qn('metadataFormat'));
			
			$query
				->insert($database->qn('#__joai_harvests'))
				->columns($columns)
				->values(array((int)$category->id.",".$database->q($url).",".$database->q($metadataFormat)));
			
			$database->setQuery($query);				
			$database->execute();
		}
		
		return true;
	}
	
	public function onContentAfterDelete($context, $category)
	{
		if ($context != 'com_categories.category')
		{
			return true;
		}

		$data = JFactory::getApplication()->input->post->get('jform', array(), 'array');
	
		if ($category->extension == 'com_jspace')
		{
			$database = JFactory::getDbo();
			$query = $database->getQuery(true);
			
			$query
				->delete($database->qn('#__joai_harvests'))
				->where($database->qn('catid').'='.(int)$category->id);
				
			$database->setQuery($query);
			$database->execute();
		}
		
		return true;
	}
}