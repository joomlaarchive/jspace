<?php
/**
 * @copyright   Copyright (C) 2014 KnowledgeArc Ltd. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */
namespace JSpace\Archive;

\JLoader::import('jspace.factory');
\JLoader::import('jspace.archive.asset');
\JLoader::import('jspace.filesystem.file');

/**
 * A high level archive object.
 */
abstract class Object extends \JObject
{
    protected $_metadata;

    public function __construct($identifier = 0)
    {
        \JTable::addIncludePath(JPATH_ROOT.'/administrator/components/com_jspace/tables/');

        \JLog::addLogger(array());

        $this->_metadata = new \Joomla\Registry\Registry;

        if (!empty($identifier))
        {
            $this->load($identifier);
        }
        else
        {
            $this->id = (isset($this->id)) ? $this->id : null;
        }

        if (isset($this->metadata))
        {
            $this->set('metadata', $this->metadata);
        }
    }

    // @todo Override until JObject declares __set.
    public function set($name, $value = null)
    {
        switch ($name)
        {
            case 'metadata':
                // reset the registry when metadata set.
                $this->_metadata = new \Joomla\Registry\Registry;

                if (is_array($value))
                {
                    $this->_metadata->loadArray($value);
                }
                else if (is_a($value, 'Joomla\Registry\Registry'))
                {
                    $this->_metadata = $value;
                }
                else if (is_string($value))
                {
                    $this->_metadata->loadString($value);
                }
                else
                {
                    throw new Exception('Invalid metadata format. Not a JRegistry, array or string.');
                }

                $this->$name = (string)$this->_metadata;

                break;

            default:
                return parent::set($name, $value);
                break;
        }
    }

    // @todo Override until JObject declares __get.
    public function get($name, $default = null)
    {
        switch ($name)
        {
            case 'metadata':
                return $this->_metadata;
                break;

            default:
                return parent::get($name, $default);
                break;
        }
    }
}