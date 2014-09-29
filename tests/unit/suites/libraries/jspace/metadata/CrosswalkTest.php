<?php
jimport('jspace.metadata.crosswalk');

class JSpaceMetadataCrosswalkTest extends PHPUnit_Framework_TestCase
{
    public function testCommonMetadata()
    {
        $metadata = new JRegistry();
        $metadata->set('resourceName', 'file.name');
        
        $crosswalk = new JSpaceMetadataCrosswalk($metadata);
        $data = $crosswalk->getCommonMetadata();
        
        $this->assertEquals(array('file.name'), $data->get('title'));
    }
    
    public function testKeying()
    {
        $metadata = new JRegistry;
        $metadata->set('creator', array('author'));
        $metadata->set('editor', array('editor'));
        $crosswalk = new JSpaceMetadataCrosswalk($metadata);
        $data = $crosswalk->getSpecialMetadata(array('dim'), true);
       var_dump($data->get('dim.dc'));
    }
    
    public function testCommonMetadataReversed()
    {
        $metadata = new JRegistry();
        $metadata->set('title', 'file.name');
        
        $crosswalk = new JSpaceMetadataCrosswalk($metadata);
        $data = $crosswalk->getCommonMetadata(true);
        
        $this->assertEquals(array('file.name'), $data->get('resourceName'));
    }
    
    public function testWalkReversed()
    {
        $metadata = new JRegistry();
        $metadata->set('title', 'file.name');
        $metadata->set('creator', array('Author 1'));
        $metadata->set('alternativeTitle', array('Alternative title 1'));
        
        $crosswalk = new JSpaceMetadataCrosswalk($metadata);
        $data = $crosswalk->getSpecialMetadata(array(), true);
        var_dump($data);
        //$this->assertEquals(array('file.name'), $data->get('resourceName'));
    }

    public function testSpecialMetadata()
    {
        $file = dirname(__FILE__).'/stubs/dc_and_citation.html';
        
        $metadata = new JRegistry();
        
        // suppress duplicate attribute errors.
        libxml_use_internal_errors(true);
        
        $dom = new DOMDocument;
        $dom->loadHTMLFile($file);
        $xpath = new DOMXPath($dom);
        
        $metas = $xpath->query('//head/meta');
        
        foreach ($metas as $meta)
        {
             $metadata->set(JString::strtolower($meta->getAttribute('name')), JString::strtolower($meta->getAttribute('content')));
        }
        
        $crosswalk = new JSpaceMetadataCrosswalk($metadata);
        $data = $crosswalk->getSpecialMetadata();
        
        $this->assertEquals(array('purifying selection can obscure the ancient age of viral lineages'), $data->get('title'));
        $this->assertEquals(array('10.1093/molbev/msr170'), $data->get('identifier'));
        $this->assertEquals(array(), $crosswalk->getTags());
    }
    
    public function testMultipleNamespaces()
    {
        $file = dirname(__FILE__).'/stubs/dc_and_dcterms.html';
        
        $metadata = new JRegistry();
        
        $dom = new DOMDocument;
        $dom->loadHTMLFile($file);
        $xpath = new DOMXPath($dom);
        
        $metas = $xpath->query('//head/meta');
        
        foreach ($metas as $meta)
        {
            $metadata->set(JString::strtolower($meta->getAttribute('name')), $meta->getAttribute('content'));
        }
        
        $metadata->set('resourceName', 'file.name');
        
        $crosswalk = new JSpaceMetadataCrosswalk($metadata);
        $data = $crosswalk->walk();

        $this->assertEquals(array('Ebola hemorrhagic fever'), $data->get('title'));
        $this->assertEquals(array('epidemiology'), $data->get('keyword'));        
        $this->assertEquals(array('epidemiology'), $crosswalk->getTags());
        $this->assertEquals(array('file.name'), $data->get('name'));
    }
    
    public function testCrosswalkFile()
    {
        // need a local file but stream_get_meta_data must use file metadata provided by server.
        $fp = fopen('http://cdn.joomla.org/template/menu/joomla.jpg', 'r');
        
        $metadata = stream_get_meta_data($fp);
        
        $registry = new JRegistry;
        
        foreach ($metadata['wrapper_data'] as $meta)
        {
            $parts = explode(':', $meta, 2);
            
            if (count($parts) == 2)
            {
                list($key, $value) = $parts;

                $registry->set($key, JString::trim($value));
            }
        }
        
        $crosswalk = new JSpaceMetadataCrosswalk($registry);
        
        $this->assertEquals(array('5181'), $crosswalk->walk()->get('contentLength'));
        $this->assertEquals(array('image/jpeg'), $crosswalk->walk()->get('contentType'));
        $this->assertEquals(array('Tue, 19 Nov 2013 21:08:57 GMT'), $crosswalk->walk()->get('modified'));
    }
    
    public function testMetadataMultipleValues()
    {
        $metadata = new JRegistry();
        $metadata->set('dc.title', 'Title');
        $metadata->set('dc.subject', array('Keyword1', 'Keyword2'));
        $metadata->set('dc.identifier', array('doi:url', 'http://hdl.handle.net/12345'));
        
        $crosswalk = new JSpaceMetadataCrosswalk($metadata);
        $data = $crosswalk->walk();
        
        $this->assertEquals(array('Title'), $data->get('title'));
        $this->assertEquals(array('Keyword1', 'Keyword2'), $data->get('keyword'));
        $this->assertEquals(array('doi:url', 'http://hdl.handle.net/12345'), $crosswalk->getIdentifiers());
    }
}