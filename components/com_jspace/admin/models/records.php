<?php
/**
 * @package     JSpace.Component
 * @subpackage  Model
 *
 * @copyright   Copyright (C) 2014 KnowledgeArc Ltd. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */
 
defined('_JEXEC') or die;

/**
 * Models the display and management of multiple JSpace records.
 *
 * @package     JSpace.Component
 * @subpackage  Model
 */
class JSpaceModelRecords extends JModelList
{
	public function __construct($config = array())
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = array(
					'id', 'r.id',
					'title', 'r.title',
					'alias', 'r.alias',
					'checked_out', 'r.checked_out',
					'checked_out_time', 'r.checked_out_time',
					'catid', 'c.catid', 'category_title',
					'state', 'r.published',
					'access', 'r.access', 'access_level',
					'created', 'r.created',
					'created_by', 'r.created_by',
					'ordering', 'r.ordering',
					'language', 'r.language',
					'hits', 'r.hits',
					'publish_up', 'r.publish_up',
					'publish_down', 'r.publish_down',
					'published', 'r.published',
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
		parent::populateState('r.title', 'asc');
	
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
	
		$table = $this->getTable('Record', 'JSpaceTable');
		$fields = array();
		
		foreach ($table->getFields() as $field)
		{			
			$fields[] = 'r.'.$db->qn($field->Field);
		}

		$query->select($this->getState('list.select', $fields));
		
		$query			
			->from('#__jspace_records AS r')
			->where("NOT r.alias = 'root'");
		
		// Get the parent title.
		$query
			->select('r2.title AS parent_title')
			->join('LEFT', '#__jspace_records AS r2 ON r.parent_id = r2.id');			

		// Get the ancestry count.
		$query
			->select('COUNT(ra.ancestor) AS level')
			->join('INNER', '#__jspace_record_ancestors AS ra ON r.id = ra.decendant')			
			->order(array('ra.ancestor'));
		
		// Join over the language
		$query->select('l.title AS language_title')
		->join('LEFT', $db->quoteName('#__languages') . ' AS l ON l.lang_code = r.language');
		
		if (JLanguageAssociations::isEnabled())
		{
			$query->select('COUNT(asso2.id)>1 as association')
			->join('LEFT', '#__associations AS asso ON asso.id = r.id AND asso.context='.$db->quote('com_jspace.record'))
			->join('LEFT', '#__associations AS asso2 ON asso2.key = asso.key');
		}
	
		// Join over the users for the checked out user.
		$query->select('uc.name AS editor')
		->join('LEFT', '#__users AS uc ON uc.id=r.checked_out');
	
		// Join over the asset groups.
		$query->select('ag.title AS access_level')
		->join('LEFT', '#__viewlevels AS ag ON ag.id = r.access');
		
		// Join over the users for the author.
		$query->select('ua.name AS author_name')
		->join('LEFT', '#__users AS ua ON ua.id = r.created_by');
	
		// Join over the categories.
		$query->select('c.id as catid, c.title AS category_title')
		->join('LEFT', '#__categories AS c ON c.id = r.catid');
		
		// Filter by access level.
		if ($access = $this->getState('filter.access'))
		{
			$query->where('r.access = ' . (int) $access);
		}
		
		// Implement View Level Access
		if (!$user->authorise('core.admin'))
		{
			$groups = implode(',', $user->getAuthorisedViewLevels());
			$query->where('r.access IN (' . $groups . ')');
		}
		
		// Filter by published state
		$published = $this->getState('filter.published');
		
		if (is_numeric($published))
		{
			$query->where('r.published = ' . (int) $published);
		}
		elseif ($published === '')
		{
			$query->where('(r.published = 0 OR r.published = 1)');
		}
		
		// Filter by a single or group of categories.
		$baselevel = 1;
		$categoryId = $this->getState('filter.category_id');

		if (is_numeric($categoryId))
		{
				$cat_tbl = JTable::getInstance('Category', 'JTable');
				$cat_tbl->load($categoryId);
				$rgt = $cat_tbl->rgt;
				$lft = $cat_tbl->lft;
				$baselevel = (int) $cat_tbl->level;
				$query->where('c.lft >= ' . (int) $lft)
						->where('c.rgt <= ' . (int) $rgt);
		}
		elseif (is_array($categoryId))
		{
				JArrayHelper::toInteger($categoryId);
				$categoryId = implode(',', $categoryId);
				$query->where('a.catid IN (' . $categoryId . ')');
		}
		
		$query->group('r.id');

		return $query;
	}
}