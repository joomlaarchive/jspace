<?php
/**
 * @copyright   Copyright (C) 2014 KnowledgeArc Ltd. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */
namespace JSpace\Archive;

/**
 * Provides JSpace Schema.
 */
class Schema
{
    /**
     * Array of schema rules.
     *
     * @var    array
     */
    protected static $rules = array();

    /**
     * Array of user groups.
     *
     * @var    array
     */
    protected static $userGroups = array();

    /**
     * Array of user group paths.
     *
     * @var    array
     */
    protected static $userGroupPaths = array();

    /**
     * Gets a list of schema access rules for the specified category.
     *
     * @param  int  $categoryId  The id of teh category.
     */
    public static function getRules($categoryId)
    {
        // Preload all groups
        if (empty(self::$rules))
        {
            $category = JTable::getInstance('Category', 'JTable');
            $category->load($categoryId);

            $params = new JRegistry($category->params);

            self::$rules = JArrayHelper::fromObject($params->get('jspace_schemas', ''));
        }

        return self::$rules;
    }

    /**
     * Checks whether the specified group is using the specified schema.
     *
     * @param   int   $group       A group id.
     * @param   int   $categoryId  A category id.
     *
     * @return  bool  True if the specified group can use the schema, false otherwise.
     */
    public function isUsed($group, $categoryId)
    {
        $used = -1;

        $rules = self::getRules($categoryId);

        if ($groups = JArrayHelper::getValue($rules, $this->name))
        {
            if (JArrayHelper::getValue($groups, $group, '', 'string') !== '')
            {
                $used = JArrayHelper::getValue($groups, $group, 0, 'int');
            }
        }

        return $used;
    }

    /**
     * Checks whether the specified group has access to a schema, either directly or through
     * inheritance.
     *
     * @param   mixed  $group       A group id or an array of group ids.
     * @param   int    $categoryId  A category id.
     *
     * @return  bool  True if the specified group can use the schema, false otherwise.
     */
    public function canUse($group, $categoryId)
    {
        $used = true;

        $rules = self::getRules($categoryId);

        foreach (self::getGroupPath($group) as $groupId)
        {
            if (($result = self::isUsed($groupId, $categoryId)) !== -1)
            {
                $used = (bool)$result;
            }
        }

        return $used;
    }

    /**
     * Gets the parent groups that a leaf group belongs to in its branch back to the root of the tree
     * (including the leaf group id).
     *
     * @param   mixed  $groupId  An integer or array of integers representing the identities to check.
     *
     * @return  mixed  True if allowed, false for an explicit deny, null for an implicit deny.
     */
    protected static function getGroupPath($groupId)
    {
        // Preload all groups
        if (empty(self::$userGroups))
        {
            $db = JFactory::getDbo();
            $query = $db->getQuery(true)
                ->select('parent.id, parent.lft, parent.rgt')
                ->from('#__usergroups AS parent')
                ->order('parent.lft');
            $db->setQuery($query);
            self::$userGroups = $db->loadObjectList('id');
        }

        // Make sure groupId is valid
        if (!array_key_exists($groupId, self::$userGroups))
        {
            return array();
        }

        // Get parent groups and leaf group
        if (!isset(self::$userGroupPaths[$groupId]))
        {
            self::$userGroupPaths[$groupId] = array();

            foreach (self::$userGroups as $group)
            {
                if ($group->lft <= self::$userGroups[$groupId]->lft && $group->rgt >= self::$userGroups[$groupId]->rgt)
                {
                    self::$userGroupPaths[$groupId][] = $group->id;
                }
            }
        }

        return self::$userGroupPaths[$groupId];
    }
}