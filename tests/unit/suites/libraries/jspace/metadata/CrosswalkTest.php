<?php
jimport('jspace.metadata.crosswalk');

class JSpaceMetadataCrosswalkTest extends PHPUnit_Framework_TestCase
{
	public function testOAIDC()
	{
		$registry = new JRegistry();
		$registry->set('dc:title', 'Title');
		$registry->set('dc:creator', 'Author');
		$registry->set('dc:subject', array('Keyword 1', 'Keyword 2'));
		
		$equals = new JRegistry();
		$equals->set('title', 'Title');
		$equals->set('author', 'Author');
		$equals->set('keyword', array('Keyword 1', 'Keyword 2'));		
		
		$crosswalk = new JSpaceMetadataCrosswalk($registry, 'oai_dc');
		$this->assertEquals($equals->toArray(), $crosswalk->walk());
	}

    public function testQDC()
    {
        $registry = new JRegistry();
        $registry->set('dc:title', array('Title'));
        $registry->set('dc:creator', array('Author'));
        $registry->set('dc:subject', array('Keyword 1', 'Keyword 2'));
        
        $equals = new JRegistry();
        $equals->set('title', array('Title'));
        $equals->set('author', array('Author'));
        $equals->set('keyword', array('Keyword 1', 'Keyword 2'));
        
        $crosswalk = new JSpaceMetadataCrosswalk($registry, 'qdc');
        $this->assertEquals($equals->toArray(), $crosswalk->walk());
    }

    public function testDSpace()
    {
        $equals = array();
        $equals['dc.title']='Title';
        $equals['dc.contributor.author']='Author';
        $equals['dc.subject']=array('Keyword 1', 'Keyword 2');
        
        $registry = new JRegistry();
        $registry->set('title', 'Title');
        $registry->set('author', 'Author');
        $registry->set('keyword', array('Keyword 1', 'Keyword 2'));       

        $crosswalk = new JSpaceMetadataCrosswalk($registry, 'dspace');

        $this->assertEquals($equals, $crosswalk->walk(true));
    }
}