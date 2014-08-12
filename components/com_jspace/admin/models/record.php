<?php
/**
 * @package     JSpace.Component
 * @subpackage  Model
 *
 * @copyright   Copyright (C) 2014 KnowledgeArc Ltd. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */
 
defined('_JEXEC') or die;

jimport('jspace.archive.record');
jimport('jspace.archive.asset');
jimport('jspace.html.assets');
jimport('jspace.clamav.client');

/**
 * Models the management of a JSpace record.
 *
 * @package     JSpace.Component
 * @subpackage  Model
 */
class JSpaceModelRecord extends JModelAdmin
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
                $associations = JLanguageAssociations::getAssociations(
                    $this->option, 
                    '#__jspace_records', 
                    $this->context, 
                    $item->id, 
                    'id', 
                    null, 
                    null);

                foreach ($associations as $tag => $association)
                {
                    $item->associations[$tag] = $association->id;
                }
            }
        }

        if (!($parent = JFactory::getApplication()->input->getInt('parent')))
        {
            $parent = $item->parent_id;
        }

        if ($parent)
        {
            $record = JSpaceRecord::getInstance($parent);
            $item->parentTitle = $record->title;
        }
        
        // Add tags.
        $item->tags = new JHelperTags;
        $item->tags->getTagIds($item->id, $this->context);
        
        // Override the base user data with any data in the session.
        $data = $app->getUserState('com_jspace.edit.record.data', array());
        foreach ($data as $k => $v)
        {
            $item->$k = $v;
        }
        
        $dispatcher = JEventDispatcher::getInstance();
        JPluginHelper::importPlugin('content');
        
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

        if ($loadData) 
        {
            $data = $form->getData()->toArray();
        }

        // if the parent id is not in the querystring, try to get it from the submitted data.
        if (!($parentId = JFactory::getApplication()->input->getInt('parent')))
        {
            $parentId = JArrayHelper::getValue($data, 'parent_id');
        }

        // show the parent if it is specified, otherwise make the category selectable.
        if ($parentId)
        {
            $parent = JSpaceRecord::getInstance($parentId);
            $form->setFieldAttribute('catid', 'type', 'hidden');
            $form->setValue('catid', null, $parent->catid);
            $form->setValue('parent_id', null, $parentId);
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
        $app = JFactory::getApplication();

        $data = $this->getItem();
        
        $this->preprocessData($this->context, $data);

        return $data;
    }

    protected function preprocessForm(JForm $form, &$data, $group = 'content')
    {
        // force to array (perhaps move to $this->loadFormData())
        if (is_a($data, "JObject"))
        {
            $data = $data->getProperties();
        }
        
        // try to get the schema from the posted data if it isn't in $data.
        if (!($schema = JArrayHelper::getValue($data, 'schema')))
        {
            $tmp = JFactory::getApplication()->input->post->get('jform', array(), 'array');
            
            $schema = JArrayHelper::getValue($tmp, 'schema');
        }

        if ($schema) 
        {
            $path = JPATH_ROOT.'/administrator/components/com_jspace/models/forms/schemas/'.$schema.'.xml';
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
            $fieldset->addAttribute('description', 'COM_JSPACE_RECORD_ASSOCIATIONS_FIELDSET_DESC');
            $add = false;
                
            foreach ($languages as $tag => $language)
            {
                if (empty($data['language']) || $tag != $data['language'])
                {
                    $add = true;
                    $field = $fieldset->addChild('field');
                    $field->addAttribute('name', $tag);
                    $field->addAttribute('type', 'JSpace.Record');
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
        
        if ($metadata = JArrayHelper::getValue($data, 'metadata'))
        {
            $data['metadata'] = $this->_mapToSchemalessMetadata($metadata, $form);
        }
    }

    public function getTable($type = 'Record', $prefix = 'JSpaceTable', $config = array())
    {
        return parent::getTable($type, $prefix, $config);
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
        $pk   = (!empty($data['id'])) ? $data['id'] : (int) $this->getState('record.id');
        $record = JSpaceRecord::getInstance($pk);
        
        $metadata = JArrayHelper::getValue($data, 'metadata');
        
        $data['metadata'] = $this->_mapFromSchemalessMetadata($metadata);
        
        // Bind the data.
        if (!$record->bind($data))
        {
            throw new Exception(JText::_('PLG_JSPACE_ASSETSTORE_ERROR_WARNFILETOOLARGE'), 413);

            return false;
        }
        
        // All assets' files should be an array before being saved.
        $collection = JSpaceHtmlAssets::getCollection();
        
        try
        {
            // Store the data.
            if (!$record->save($collection))
            {
                return false;
            }
        }
        catch (Exception $e)
        {
            JLog::addLogger(array());
            JLog::add($e->getMessage(), JLog::ERROR, 'jspace');
            $this->setError(JText::_('JERROR_AN_ERROR_HAS_OCCURRED'));
            return false;
        }
        
        $this->setState('record.id', $record->id);

        $this->setState($this->getName() . '.new', ($pk ? false : true));

        return true;
    }

    public function delete(&$pks)
    {
        foreach ($pks as $pk)
        {
            $record = JSpaceRecord::getInstance($pk);
            
            if (!$record->delete())
            {
                return false;
            }
        }
        
        return true;
    }

    public function validate($form, $data, $group = null)
    {
        $dispatcher = JEventDispatcher::getInstance();
        JPluginHelper::importPlugin('content');
        
        $result = $dispatcher->trigger('onJSpaceRecordBeforeValidate', array($form, $data, $group));
        
        if (in_array(false, $result, true))
        {
            return false;
        }
        
        $params = JComponentHelper::getParams('com_jspace');
        
        // A general post size check of the incoming form.
        $contentLength = $_SERVER['CONTENT_LENGTH'];
        $uploadMaxSize = (int)($params->get('upload_maxsize', 10)) * 1024 * 1024;
        $maxFileSize = (int)(ini_get('upload_max_filesize')) * 1024 * 1024;
        $maxPostSize = (int)(ini_get('post_max_size')) * 1024 * 1024;
        $memoryLimit = (int)(ini_get('memory_limit')) * 1024 * 1024;
        
        if ($uploadMaxSize)
        {		
            if ($contentLength > $uploadMaxSize ||
                $contentLength > $maxFileSize ||
                $contentLength > $maxPostSize ||
                (($contentLength > $memoryLimit) && ((int)(ini_get('memory_limit')) != -1)))
            {
                JFactory::getApplication()->enqueueMessage(JText::_('COM_JSPACE_ERROR_WARNUPLOADTOOLARGE'), 'warning');
                return false;
            }
        }		
        
        if ($data = parent::validate($form, $data, $group))
        {
            $result = $dispatcher->trigger('onJSpaceRecordAfterValidate', array($form, $data, $group));
            
            if (in_array(false, $result, true))
            {
                return false;
            }
        }
        
        return $data;
    }

    /**
     * Gets an instance of the JSpaceAsset class based on the id parameter.
     *
     * @param   int $id      The id of the JSpaceAsset to retrieve.
     *
     * @return  JSpaceAsset  An instance of the JSpaceAsset class based on the id parameter.
     */
    public function getAsset($id)
    {
        return JSpaceAsset::getInstance($id);
    }

    /**
     * Deletes an asset from the record based on the id parameter.
     * 
     * @param  int  $assetId  The id of the asset to be deleted.
     */
    public function deleteAsset($assetId)
    {
        $ids = $assetId;

        if (!is_array($assetId))
        {
            $ids = array($assetId);
        }
        
        foreach ($ids as $id)
        {
            $asset = $this->getAsset($id);
            $asset->delete();
        }
    }

    /**
     * Gets an instance of the JSpaceReference class based on the id and context parameter.
     *
     * @param   int $id      The id of the JSpaceAsset to retrieve.
     *
     * @return  JSpaceReference  An instance of the JSpaceAsset class based on the id parameter.
     */
    public function getReference($id)
    {
        return JSpaceReference::getInstance($id);
    }

    /**
     * Deletes a reference from the record based on the id and context parameters.
     * 
     * @param  int     $id       The id of the reference to be deleted.
     * @param  string  $context  The context of the reference to be deleted.
     */
    public function deleteReference($id, $context)
    {
        $asset = $this->getAsset($id);
        $asset->delete();
    }

    /**
     * Binds the asset metadata to the record's metadata.
     * 
     * @param  int  $assetId  The id of the asset to use.
     */
    public function useAssetMetadata($assetId)
    {
        $asset = JSpaceAsset::getInstance($assetId);

        $record = JSpaceRecord::getInstance($asset->record_id);
        $record->bindAssetMetadata($asset->id);
    }

    /**
     * Transforms schemaless metadata to/from name/value pairs into a format JSpace's record storage 
     * accepts.
     *
     * This method will search fields in the record's schema form, only transforming name/value pairs 
     * which match the JSpace.MetadataSchemaless field type.
     * 
     * @param   array  $metadata  An array of metadata values.
     * 
     * @return  array  The transformed metadata.
     */
    private function _mapFromSchemalessMetadata($metadata)
    {
        // re-map schemaless metadata name/value pairs to metadata.
        foreach ($this->getForm()->getFieldsets('metadata') as $fieldset)
        {
            foreach ($this->getForm()->getFieldset($fieldset->name) as $field)
            {
                if (JString::strtolower($field->type) == 'jspace.metadataschemaless')
                {
                    $nameValuePairs = JArrayHelper::getValue($metadata, $field->fieldname);
                    
                    foreach ($nameValuePairs as $nameValuePair)
                    {
                        if ($name = JArrayHelper::getValue($nameValuePair, 'name'))
                        {
                            if (!array_key_exists($name, $metadata))
                            {
                                $metadata[$name] = array();
                            }
                            
                            $metadata[$name][] = JArrayHelper::getValue($nameValuePair, 'value');
                        }
                    }
                    
                    unset($metadata[$field->fieldname]);
                }
            }
        }
        
        return $metadata;
    }
    
    /**
     * Transforms the stored metadata into a name/value format the schemaless metadata understands.
     *
     * This method will search fields in the record's schema form, only transforming name/value pairs 
     * which match the JSpace.MetadataSchemaless field type.
     * 
     * @param   array  $metadata  An array of metadata values.
     * @param   JForm  $form      The record's form object.
     * 
     * @return  array  The transformed metadata.
     */
    private function _mapToSchemalessMetadata($metadata, JForm $form)
    {
        // first find the metadataschemaless field.
        $fields = $form->getGroup('metadata'); 
        $schemaless = null;
        
        while (($field = current($fields)) && !$schemaless)
        {
            if ($field->type == 'jspace.metadataschemaless')
            {
                $schemaless = $field->fieldname;
            }
            
            next($fields);
        }
    
        // if found, load it with unmapped values.
        if ($schemaless)
        {
            foreach ($metadata as $key=>$array)
            {
                if (!$form->getField($key, 'metadata'))
                {
                    if (!JArrayHelper::getValue($metadata, $schemaless))
                    {
                        $metadata['$schemaless'] = array();
                    }
                    
                    foreach ($array as $value)
                    {
                        $metadata['$schemaless'][] = array('name'=>$key,'value'=>$value);
                    }
                    
                    unset($metadata[$key]);
                }
            }
        }
        
        return $metadata;
    }
}