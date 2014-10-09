<?php
/**
 * @package     JSpace.Component
 * @subpackage  Model
 * @copyright   Copyright (C) 2005 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

jimport('jspace.archive.record');

/**
 * Models the display and management of a single JSpace record, its children, assets and
 * references.
 *
 * @package     JSpace.Component
 * @subpackage  Model
 */
class JSpaceModelRecord extends JModelItem
{
    public function __construct($config = array())
    {
        parent::__construct($config);

        // Guess the context as Option.ModelName.
        if (empty($this->context))
        {
            $this->context = JString::strtolower($this->option . '.' . $this->getName());
        }

        $this->typeAlias = $this->context;

        //@TODO Joomla hasn't standardized on _context or context.
        $this->_context = $this->context;
    }

    protected function populateState()
    {
        $app = JFactory::getApplication('site');

        // Load state from the request.
        $pk = $app->input->getInt('id');
        $this->setState('record.id', $pk);

        $offset = $app->input->getUInt('limitstart');
        $this->setState('list.offset', $offset);

        // Load the parameters.
        $params = $app->getParams();
        $this->setState('params', $params);

        // TODO: Tune these values based on other permissions.
        $user = JFactory::getUser();

        if ((!$user->authorise('core.edit.state', 'com_jspace')) && (!$user->authorise('core.edit', 'com_jspace')))
        {
            $this->setState('filter.published', 1);
            $this->setState('filter.archived', 2);
        }

        $this->setState('filter.language', JLanguageMultilang::isEnabled());
    }

    public function getItem($pk = null)
    {
        $user = JFactory::getUser();

        $pk = (!empty($pk)) ? $pk : (int) $this->getState('record.id');

        if (!$this->get('item'))
        {
            try
            {
                $record = JSpaceRecord::getInstance($pk);

                if (!($record->id))
                {
                    throw new Exception(JText::_('COM_JSPACE_ERROR_RECORD_NOT_FOUND'), 404);
                }

                $published = $this->getState('filter.published');
                $archived = $this->getState('filter.archived');

                // Check for published state if filter set.
                if (((is_numeric($published)) || (is_numeric($archived))) && (($record->published != $published) && ($record->published != $archived)))
                {
                    throw new Exception(JText::_('COM_JSPACE_ERROR_RECORD_NOT_FOUND'), 404);
                }

                $record->params = clone $this->getState('params');

                if (!$user->get('guest'))
                {
                    $userId = $user->get('id');
                    $asset = 'com_jspace.record.' . $record->id;

                    // Check general edit permission first.
                    if ($user->authorise('core.edit', $asset))
                    {
                        $record->params->set('access-edit', true);
                    }

                    // Now check if edit.own is available.
                    elseif (!empty($userId) && $user->authorise('core.edit.own', $asset))
                    {
                        // Check for a valid user and that they are the owner.
                        if ($userId == $data->created_by)
                        {
                            $data->params->set('access-edit', true);
                        }
                    }
                }

                // Compute view access permissions.
                if ($access = $this->getState('filter.access'))
                {
                    // If the access filter has been set, we already know this user can view.
                    $record->params->set('access-view', true);
                }
                else
                {
                    // If no access filter is set, the layout takes some responsibility for display of limited information.
                    $user = JFactory::getUser();
                    $groups = $user->getAuthorisedViewLevels();

                    if ($record->getCategory())
                    {
                        $record->params->set('access-view', in_array($record->access, $groups) && in_array($record->getCategory()->access, $groups));
                    }
                    else
                    {
                        $record->params->set('access-view', false);
                    }
                }

                $this->set('item', $record);
            }
            catch (Exception $e)
            {
                if ($e->getCode() == 404)
                {
                    // Need to go thru the error handler to allow Redirect to work.
                    JError::raiseError(404, $e->getMessage());
                }
                else
                {
                    throw $e;
                    $this->set('item', false);
                }
            }
        }

        return $this->get('item');
    }

    public function getLanguage()
    {
        if ($this->getItem()->language === '*')
        {
            return JFactory::getConfig()->get('language');
        }
        else
        {
            return $this->item->language;
        }
    }
}