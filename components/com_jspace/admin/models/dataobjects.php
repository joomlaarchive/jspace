<?php
defined('_JEXEC') or die;

class JSpaceModelDataObjects extends JModelList
{
	public function __construct($config = array())
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = array(
					'id', 'do.id',
					'title', 'do.title',
					'alias', 'do.alias',
					'checked_out', 'do.checked_out',
					'checked_out_time', 'do.checked_out_time',
					'catid', 'c.catid', 'category_title',
					'state', 'do.published',
					'access', 'do.access', 'access_level',
					'created', 'do.created',
					'created_by', 'do.created_by',
					'ordering', 'do.ordering',
					'language', 'do.language',
					'hits', 'do.hits',
					'publish_up', 'do.publish_up',
					'publish_down', 'do.publish_down',
					'published', 'do.published',
					'author_id',
					'category_id'
			);
		
			if (JLanguageAssociations::isEnabled())
			{
				$config['filter_fields'][] = 'association';
			}
		}
		
		parent::__construct($config);
	}
	
	protected function populateState($ordering = null, $direction = null)
	{
		$app = JFactory::getApplication();
	
		// Adjust the context to support modal layouts.
		if ($layout = $app->input->get('layout'))
		{
			$this->context .= '.' . $layout;
		}
	
		$search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
		$this->setState('filter.search', $search);
	
		$access = $this->getUserStateFromRequest($this->context . '.filter.access', 'filter_access', 0, 'int');
		$this->setState('filter.access', $access);
	
		$authorId = $app->getUserStateFromRequest($this->context . '.filter.author_id', 'filter_author_id');
		$this->setState('filter.author_id', $authorId);
	
		$published = $this->getUserStateFromRequest($this->context . '.filter.published', 'filter_published', '');
		$this->setState('filter.published', $published);
	
		$categoryId = $this->getUserStateFromRequest($this->context . '.filter.category_id', 'filter_category_id');
		$this->setState('filter.category_id', $categoryId);
	
		$language = $this->getUserStateFromRequest($this->context . '.filter.language', 'filter_language', '');
		$this->setState('filter.language', $language);

		// List state information.
		parent::populateState('do.title', 'asc');
	
		// Force a language
		$forcedLanguage = $app->input->get('forcedLanguage');
	
		if (!empty($forcedLanguage))
		{
			$this->setState('filter.language', $forcedLanguage);
			$this->setState('filter.forcedLanguage', $forcedLanguage);
		}
	}
	
	protected function getListQuery()
	{
		// Create a new query object.
		$db = $this->getDbo();
		$query = $db->getQuery(true);
		$user = JFactory::getUser();
		$app = JFactory::getApplication();
	
		$table = $this->getTable('DataObject', 'JSpaceTable');
		$fields = array();
		
		foreach ($table->getFields() as $field)
		{			
			$fields[] = 'do.'.$db->qn($field->Field);
		}

		$query->select($this->getState('list.select', $fields));
		
		$query			
			->from('#__jspace_dataobjects AS do')
			->where("NOT do.alias = 'root'");
		
		// Get the parent title.
		$query
			->select('do2.title AS parent_title')
			->join('LEFT', '#__jspace_dataobjects AS do2 ON do.parent_id = do2.id');			

		// Get the ancestry count.
		$query
			->select('COUNT(doa.ancestor) AS level')
			->join('INNER', '#__jspace_dataobject_ancestors AS doa ON do.id = doa.decendant')			
			->order(array('doa.ancestor'));
		
		// Join over the language
		$query->select('l.title AS language_title')
		->join('LEFT', $db->quoteName('#__languages') . ' AS l ON l.lang_code = do.language');
		
		if (JLanguageAssociations::isEnabled())
		{
			$query->select('COUNT(asso2.id)>1 as association')
			->join('LEFT', '#__associations AS asso ON asso.id = do.id AND asso.context=' . $db->quote('com_jspace.dataobject'))
			->join('LEFT', '#__associations AS asso2 ON asso2.key = asso.key');
		}
	
		// Join over the users for the checked out user.
		$query->select('uc.name AS editor')
		->join('LEFT', '#__users AS uc ON uc.id=do.checked_out');
	
		// Join over the asset groups.
		$query->select('ag.title AS access_level')
		->join('LEFT', '#__viewlevels AS ag ON ag.id = do.access');
		
		// Join over the users for the author.
		$query->select('ua.name AS author_name')
		->join('LEFT', '#__users AS ua ON ua.id = do.created_by');
	
		// Join over the categories.
		$query->select('c.id as catid, c.title AS category_title')		
		->join('LEFT', '#__jspace_dataobjects_categories AS doc ON doc.dataobject_id = do.id')
		->join('LEFT', '#__categories AS c ON c.id = doc.catid');
		
		// Filter by access level.
		if ($access = $this->getState('filter.access'))
		{
			$query->where('do.access = ' . (int) $access);
		}
		
		// Implement View Level Access
		if (!$user->authorise('core.admin'))
		{
			$groups = implode(',', $user->getAuthorisedViewLevels());
			$query->where('do.access IN (' . $groups . ')');
		}
		
		// Filter by published state
		$published = $this->getState('filter.published');
		
		if (is_numeric($published))
		{
			$query->where('do.published = ' . (int) $published);
		}
		elseif ($published === '')
		{
			$query->where('(do.published = 0 OR do.published = 1)');
		}
		
		$query->group('do.id');

		return $query;
	}
}