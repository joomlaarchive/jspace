<?php
require_once(JSPACEPATH_TESTS.'/core/case/database.php');

use JSpace\Metadata\Crosswalk;
use Joomla\Registry\Registry;
use \JString;

class CrosswalkTest extends \TestCaseDatabase
{
    public function testCommonMetadata()
    {
        $metadata = array();
        $metadata['resourceName'] = 'file.name';
        $metadata['Last-Save-Date'] = '2015-01-01';

        $crosswalk = new Crosswalk($metadata);
        $data = $crosswalk->getCommonMetadata();

        $this->assertEquals(array('title', 'modified'), array_keys($data));
        $this->assertEquals(array('file.name'), $data['title']);
        $this->assertEquals(array('2015-01-01'), $data['modified']);
    }

    public function testSpecialMetadata()
    {
        $metadata = array();
        $metadata['dim:dc.contributor.author']  = array('Author Name');
        $metadata['dim:dc.editor']              = array('Editor Name');
        $metadata['dc:modified']                = array('2015-01-02');
        $metadata['dcterms:modified']           = array('2015-01-01');

        $crosswalk = new Crosswalk($metadata);
        $data = $crosswalk->getSpecialMetadata();

        $this->assertEquals(array('modified', 'creator'), array_keys($data));
        $this->assertEquals(array('Author Name'), $data['creator']);
        $this->assertEquals(array('2015-01-01'), $data['modified']);
    }

    public function testWalk()
    {
        $metadata = array();
        $metadata['resourceName']               = 'file.name';
        $metadata['Last-Save-Date']             = '2015-01-03';
        $metadata['dim:dc.contributor.author']  = array('Author Name');
        $metadata['dim:dc.editor']              = array('Editor Name');
        $metadata['dc:modified']                = array('2015-01-02');
        $metadata['dcterms:modified']           = array('2015-01-01');

        $crosswalk = new Crosswalk($metadata);
        $data = $crosswalk->walk();

        $this->assertEquals(array('modified', 'creator', 'title'), array_keys($data));
        $this->assertEquals(array('Author Name'), $data['creator']);
        $this->assertEquals(array('2015-01-03'), $data['modified']);
    }

    public function testCommonMetadataReversed()
    {
        $metadata = new Registry();
        $metadata->set('title', 'file.name');

        $crosswalk = new Crosswalk($metadata);
        $data = $crosswalk->getCommonMetadata(true);

        $this->assertEquals(array('file.name'), $data['resourceName']);
    }

    public function testSpecialMetadataReversed()
    {
        $metadata = new Registry();
        $metadata->set('title', 'file.name');
        $metadata->set('creator', array('Author 1'));
        $metadata->set('alternativeTitle', array('Alternative title 1'));

        $crosswalk = new Crosswalk($metadata);
        $data = $crosswalk->getSpecialMetadata(array('dc', 'dcterms'), true);

        $this->assertEquals(array('Author 1'), $data['dcterms:creator']);
        $this->assertEquals(array('Author 1'), $data['dc:creator']);
        $this->assertEquals(array('file.name'), $data['dc:title']);
        $this->assertEquals(array('file.name'), $data['dcterms:title']);
        $this->assertEquals(array('Alternative title 1'), $data['dcterms:alternative']);
    }

    public function testGetIdentifiers()
    {
        $metadata = array();
        $metadata['dc:identifier'] = array('doi:url', 'http://hdl.handle.net/12345');

        $crosswalk = new Crosswalk($metadata);
        $data = $crosswalk->walk();

        $this->assertEquals(array('doi:url', 'http://hdl.handle.net/12345'), $crosswalk->getIdentifiers());
    }

    public function testGetTags()
    {
        $metadata = array();
        $metadata['dc:subject'] = array('Keyword 1', 'Keyword 2');
        $metadata['dc:type'] = array('Type 1');

        $crosswalk = new Crosswalk($metadata);
        $data = $crosswalk->walk();

        $this->assertEquals(array('Keyword 1', 'Keyword 2', 'Type 1'), $crosswalk->getTags());
    }

    public function testSpecialMetadataEmbeddedInHtml()
    {
        $file = dirname(__FILE__).'/stubs/dc_and_citation.html';

        $metadata = array();

        // suppress duplicate attribute errors.
        libxml_use_internal_errors(true);

        $dom = new DOMDocument;
        $dom->loadHTMLFile($file);
        $xpath = new DOMXPath($dom);

        $metas = $xpath->query('//head/meta');

        foreach ($metas as $meta) {
            $parts = explode('.', JString::strtolower($meta->getAttribute('name')), 2);

            $schemaKey = \JArrayHelper::getValue($parts, 0);

            if (array_search($schemaKey, array('dc', 'dcterms')) !== false) {
                $schemaKey = implode(':', $parts);
            } else {
                $schemaKey = JString::strtolower($meta->getAttribute('name'));
            }

            $metadata[$schemaKey] = $meta->getAttribute('content');
        }

        $crosswalk = new Crosswalk($metadata);
        $data = $crosswalk->getSpecialMetadata();

        $this->assertEquals(array('Purifying Selection Can Obscure the Ancient Age of Viral Lineages'), $data['title']);
        $this->assertEquals(array('10.1093/molbev/msr170'), $data['identifier']);
    }

    public function testMultipleNamespaces()
    {
        $file = dirname(__FILE__).'/stubs/dc_and_dcterms.html';

        $metadata = array();

        $dom = new DOMDocument;
        $dom->loadHTMLFile($file);
        $xpath = new DOMXPath($dom);

        $metas = $xpath->query('//head/meta');

        foreach ($metas as $meta) {
            $parts = explode('.', JString::strtolower($meta->getAttribute('name')), 2);

            $schemaKey = \JArrayHelper::getValue($parts, 0);

            if (array_search($schemaKey, array('dc', 'dcterms')) !== false) {
                $schemaKey = implode(':', $parts);
            } else {
                $schemaKey = JString::strtolower($meta->getAttribute('name'));
            }

            $metadata[$schemaKey] = $meta->getAttribute('content');
        }

        $metadata['resourceName'] = 'file.name';

        $crosswalk = new Crosswalk($metadata);
        $data = $crosswalk->walk();

        $this->assertEquals(array('file.name'), $data['title']);
        $this->assertEquals(array('epidemiology'), $data['keyword']);
        $this->assertEquals(array('epidemiology'), $crosswalk->getTags());
    }

    public function testCrosswalkFile()
    {
        $registry = \JSpace\FileSystem\File::getMetadata('http://cdn.joomla.org/template/menu/joomla.jpg');
        $crosswalk = new Crosswalk($registry);

        $this->assertEquals(array('5181'), $crosswalk->walk()['contentLength']);
        $this->assertEquals(array('image/jpeg'), $crosswalk->walk()['contentType']);
    }
}