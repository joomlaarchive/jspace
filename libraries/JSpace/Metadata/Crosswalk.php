<?php
/**
 * @copyright   Copyright (C) 2014 KnowledgeArc Ltd. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */
namespace JSpace\Metadata;

\JLoader::import('joomla.filesystem.file');

use \JFile;
use \JString;
use \Exception;

/**
 * Provides crosswalks based on a preconfigured crosswalk ruleset.
 *
 * Schema-based fields are prepended with the schema name, for example:
 *
 * dc:author
 * dcterms:author
 */
class Crosswalk extends \JObject
{
    protected $source;

    protected $metadata;

    /**
     * The crosswalk registry;
     * @var array
     */
    protected $registry;

    /**
     * Instantiates an instance of the file metadata crosswalk based on a registry file.
     *
     * @param  array   $source    The source metadata to crosswalk.
     * @param  string  $registry  The name of the crosswalk registry. Defaults to crosswalk.json.
     */
    public function __construct($source, $registry = 'crosswalk')
    {
        parent::__construct();

        $this->source = $source;

        $path = JPATH_ROOT.'/administrator/components/com_jspace/crosswalks/'.$registry.'.json';

        if (!JFile::exists($path)) {
            throw new Exception('No crosswalk file found.');
        }

        $crosswalk = json_decode(file_get_contents($path), true);

        $this->registry = $crosswalk;
    }

    /**
     * Walks the source metadata, remapping each metadata value from its current metadata key to a
     * different metadata key name as defined in the crosswalk registry.
     *
     * @return  array   An associative array of metadata values.
     */
    public function walk()
    {
        $special = $this->getSpecialMetadata();
        $common = $this->getCommonMetadata();

        return array_merge($special, $common);
    }

    /**
     * Gets metadata based on "special" crosswalk settings.
     *
     * Use this method when crosswalking from a named schema such as Dublin Core to JSpace's
     * internal metadata schema.
     *
     * @param   array  $prefixes  An array of schema prefixes.
     * @param   bool   $reverse   True to reverse the crosswalk, false otherwise. Defaults to false.
     *
     * @return  array  An associative array of metadata based on "special" crosswalk settings.
     */
    public function getSpecialMetadata($prefixes = array(), $reverse = false)
    {
        $metadata = \JArrayHelper::getValue($this->registry, 'metadata', array());
        $schemas = \JArrayHelper::getValue($metadata, 'special', array());

        $data = array();

        foreach ($schemas as $key=>$schema) {
            if ($reverse) {
                if (empty($prefixes) || array_search($key, $prefixes) === false) {
                    continue;
                }

                $source = $this->source;
            } else {
                $source = array();

                foreach ($this->source as $skey=>$svalue) {
                    if (strpos($skey, $key.':') === 0) {
                        $source[str_replace($key.':', '', $skey)] = $svalue;
                    }
                }
            }

            $data = array_merge($data, $this->mapMetadata($source, array($key=>$schema), $reverse));
        }

        return $data;
    }

    /**
     * Gets metadata based on "common" crosswalk settings.
     *
     * Use this method when crosswalking from internal metadata to an external metadata schema
     * such as Dublin Core.
     *
     * @return  array  An associative array of metadata based on "common" crosswalk settings.
     */
    public function getCommonMetadata($reverse = false)
    {
        $metadata = \JArrayHelper::getValue($this->registry, 'metadata', array());

        $schema = \JArrayHelper::getValue($metadata, 'common', array());

        return $this->mapMetadata($this->source, array('common'=>$schema), $reverse);
    }

    /**
     * Gets tags based on specified metadata.
     *
     * @return  string[]  An array of tags based on specific metadata.
     */
    public function getTags()
    {
        $tags = array();

        foreach (\JArrayHelper::getValue($this->registry, 'tags', array()) as $tag) {
            $field = \JArrayHelper::getValue($this->source, $tag);

            if (is_array($field)) {
                $tags = array_merge($tags, $field);
            } else if (!is_null($field)) {
                $tags[] = $field;
            }
        }

        return $tags;
    }

    /**
     * Gets a list of identifiers based on identifier settings.
     *
     * @return  string[]  A list of identifiers based on identifier settings.
     */
    public function getIdentifiers()
    {
        $identifiers = array();

        foreach (\JArrayHelper::getValue($this->registry, 'identifiers', array()) as $key=>$config) {
            $field = \JArrayHelper::getValue($this->source, $key);

            if (is_array($field)) {
                $identifiers = array_merge($identifiers, $field);
            } else if (!is_null($field)) {
                $identifiers[] = $field;
            }


            // clean up identifiers based on prefix settings.
            foreach (\JArrayHelper::getValue($config, 'prefix', array()) as $prefix) {
                $found = false;

                while (current($identifiers) && !$found) {
                    if (JString::strpos(current($identifiers), $prefix) === 0) {
                        $found = true;
                    }

                    next($identifiers);
                }

                if (!$found) {
                    unset($identifiers[key($identifiers)]);
                }

                reset($identifiers);
            }
        }

        return $identifiers;
    }

    /**
     * Maps a schema's metadata.
     *
     * @param   array  $registry  An associative array of metadata keys and values.
     * @param   array  $schema    The schema section of the crosswalk configuration.
     * @param   bool   $reverse   True to map targets to sources, false otherwise. Defaults to
     * false.
     *
     * @return  array  An associative array of mapped metadata keys and values.
     */
    private function mapMetadata($registry, $schema, $reverse = false)
    {
        if ($reverse) {
            $sName = 'target';
            $tName = 'source';
        } else {
            $sName = 'source';
            $tName = 'target';
        }

        $metadata = array();

        $keys = array_keys($schema);
        $prefix = \JArrayHelper::getValue($keys, 0);

        $schema = array_pop($schema);

        foreach (\JArrayHelper::getValue($schema, 'map', array()) as $mappable) {
            $skey = \JArrayHelper::getValue($mappable, $sName);
            $tkey = \JArrayHelper::getValue($mappable, $tName);

            // sometimes the key needs to be lower case to avoid poorly marked up HTML.
            $default = \JArrayHelper::getValue($registry, \JString::strtolower($skey));
            $source = \JArrayHelper::getValue($registry, $skey, $default);

            if ($source) {
                $target = \JArrayHelper::getValue($metadata, $tkey);

                if (!is_array($target)) {
                    $target = array();
                }

                if (!is_array($source)) {
                    $array = array();
                    $array[] = $source;
                    $source = $array;
                }

                if ($prefix !== 'common' && $reverse) {
                    $tkey = $prefix.':'.$tkey;
                }

                $metadata[$tkey] = array_merge($target, $source);
            }
        }

        return $metadata;
    }
}