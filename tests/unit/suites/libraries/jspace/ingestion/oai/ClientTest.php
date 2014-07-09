<?php
jimport('jspace.ingestion.oai.client');

class JSpaceIngestionOAIClientClientTest extends PHPUnit_Framework_TestCase
{
	private $uri;
	
	public function setUp()
	{
		$this->uri = $this->getMockBuilder('JUri')
        ->disableOriginalConstructor()
        ->getMock();
        
		$this->uri->expects($this->any())
              ->method('setVar')
              ->will($this->returnValue(null));
	}

    public function testPing()
    {
		$this->uri->expects($this->any())
			->method('__toString')
			->will($this->returnValue(__DIR__.'/stubs/Identify.xml'));
              
		$client = new JSpaceIngestionOAIClient($this->uri);
		$this->assertTrue($client->ping());
    }
    
    public function testIdentify()
    {
    	$this->uri->expects($this->any())
              ->method('__toString')
              ->will($this->returnValue(__DIR__.'/stubs/Identify.xml'));
		
		$client = new JSpaceIngestionOAIClient($this->uri);
		
		$element = @simplexml_load_file((string)$this->uri);
		$this->assertEquals($element, $client->identify());
    }
    
    /**
     * @expectedException InvalidArgumentException
     */
    public function testIdentifyEmptyURL()
    {
		$this->uri->expects($this->any())
              ->method('__toString')
              ->will($this->returnValue(""));

		$client = new JSpaceIngestionOAIClient($this->uri);
		
		$identify = $client->identify();
    }
    
    /**
     * @expectedException RuntimeException
     */
    public function testIdentifyInvalidURL()
    {
		$this->uri->expects($this->any())
              ->method('__toString')
              ->will($this->returnValue("http://bogusurl/"));
		
		$client = new JSpaceIngestionOAIClient($this->uri);
		
		$identify = $client->identify();
    }
    
    /**
     * Emulate e.g. http://goodurl/to/oai?extra=param
     */
    public function testIdentifyBadArgument()
    {		
		$this->uri->expects($this->any())
              ->method('__toString')
              ->will($this->returnValue(__DIR__."/stubs/Identify_badArgument.xml"));
		
		$client = new JSpaceIngestionOAIClient($this->uri);
		
		$identify = $client->identify($this->uri);
		
		$this->assertEquals('badArgument', JArrayHelper::getValue($identify->error, 'code'));
		$this->assertEquals("Unknown parameter 'test'", (string)$identify->error);
    }
    
    public function testHasMetadataFormat()
    {
		$this->uri->expects($this->any())
              ->method('__toString')
              ->will($this->returnValue(__DIR__."/stubs/ListMetadataFormats.xml"));
		
		$client = new JSpaceIngestionOAIClient($this->uri);
		
		$this->assertTrue($client->hasMetadataFormat('oai_dc'));
		$this->assertTrue($client->hasMetadataFormat('xoai'));
		$this->assertTrue($client->hasMetadataFormat('dim'));
		$this->assertTrue($client->hasMetadataFormat('qdc'));
    }
    
    public function testNoMetadataFormat()
    {
		$this->uri->expects($this->any())
              ->method('__toString')
              ->will($this->returnValue(__DIR__."/stubs/ListMetadataFormats.xml"));

		$client = new JSpaceIngestionOAIClient($this->uri);
		
		$this->assertFalse($client->hasMetadataFormat('abc'));
    }
    
    public function testHasMetadataFormatsOrder()
    {
		$this->uri->expects($this->any())
              ->method('__toString')
              ->will($this->returnValue(__DIR__."/stubs/ListMetadataFormats.xml"));
		
		$client = new JSpaceIngestionOAIClient($this->uri);
		
		$this->assertEquals(array('xoai', 'qdc', 'oai_dc'), array_keys($client->hasMetadataFormats(array('xoai', 'qdc', 'oai_dc'))));
		
		$this->assertEquals(array('ore', 'rdf'), array_keys($client->hasMetadataFormats(array('ore', 'rdf'))));
    }
}