<?php
jimport('jspace.metadata.crosswalk');

class JSpaceMetadataCrosswalkTest extends PHPUnit_Framework_TestCase
{
	public function testCrosswalk()
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
}