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
	
	public function onContentBeforeSave($context, $data, $isNew)
	{
		if ($context == 'com_categories.category')
		{
			if (isset($data->extension) && $data->extension == 'com_jspace')
			{
				$old = JTable::getInstance('Category');
				if ($old->load($data->id))
				{
					$oldParams = new JRegistry($old->params);
					$newParams = new JRegistry($data->params);
					
					if ($oldParams->get('oai_url') != $newParams->get('oai_url'))
					{
						$database = JFactory::getDbo();
						$query = $database->getQuery(true);
						$query
							->delete($database->qn('#__jspaceoai_harvests'))
							->where($database->qn('catid').'='.(int)$data->id);
							
						$database->setQuery($query);
						$database->execute();
					}
				}
			}
		}
	}
}