<?php
jimport('jspace.ingestion.oai.assetharvester');

if (!class_exists('PHPUnit_Extensions_Database_TestCase'))
{
	require_once 'PHPUnit/Extensions/Database/TestCase.php';
	require_once 'PHPUnit/Extensions/Database/DataSet/XmlDataSet.php';
	require_once 'PHPUnit/Extensions/Database/DataSet/QueryDataSet.php';
	require_once 'PHPUnit/Extensions/Database/DataSet/MysqlXmlDataSet.php';
}

class JSpaceIngestionOAIHarvesterTest extends PHPUnit_Extensions_Database_TestCase
{
	protected static $driver;
	
	protected static $url;
	
	private static $_stash;

	private $categories;
	
	public static function setUpBeforeClass()
	{
		$options = array(
			'driver' => 'sqlite',
			'database' => ':memory:',
			'prefix' => 'jos_'
		);

		try
		{
			self::$driver = JDatabaseDriver::getInstance($options);
			
			$pdo = new PDO('sqlite::memory:');
			
			$pdo->exec(file_get_contents(JSPACEPATH_TESTS.'/schema/ddl.sql'));

			$property = new \ReflectionProperty(get_parent_class(self::$driver), 'connection');
			$property->setAccessible(true);

			$property->setValue(self::$driver, $pdo);
		}
		catch (RuntimeException $e)
		{
			self::$driver = null;
		}

		if (self::$driver instanceof Exception)
		{
			self::$driver = null;
		}
		
		self::$_stash = JFactory::$database;
		JFactory::$database = self::$driver;
	}
	
	public function setUp()
	{
		if (empty(static::$driver))
		{
			$this->markTestSkipped('There is no database driver.');
		}
		
		parent::setUp();
		
		$database = JFactory::getDbo();
		
		$query = $database->getQuery(true);
		
		$select = array(
			$database->qn('c.id'), 
			$database->qn('c.language'), 
			$database->qn('c.access'), 
			$database->qn('c.published'), 
			$database->qn('c.created_user_id'), 
			$database->qn('c.params'));
		
		$query
			->select($select)
			->from($database->qn('jos_categories', 'c'))
			->where($database->qn('c.published').'='.$database->q('1'))
			->where($database->qn('c.extension').'='.$database->q('com_jspace'));
		
		$database->setQuery($query);
		
		$categories = $database->loadObjectList();
		
		foreach ($categories as $key=>$value)
		{
			$params = new JRegistry();
			$params->loadString($value->params);
			
			if ($params->get('oai_url'))
			{
				
				$categories[$key]->params = $params;
			}
			else 
			{
				unset($categories[$key]);
			}
		}
		
		$this->categories = $categories;
	}
	
	public function testSingleCategory()
	{
		$category = $this->categories[1];
		
		$harvester = new JSpaceIngestionOAIHarvester($category);
		$harvester->harvest()->ingest();
		
		$query = JFactory::getDbo()->getQuery(true);
		$this->assertEquals(0, JFactory::getDbo()->setQuery($query->select('count(*)')->from('jos_jspace_cache'))->loadResult());
		
		$query = JFactory::getDbo()->getQuery(true);
		$this->assertEquals(306, JFactory::getDbo()->setQuery($query->select('count(*)')->from('jos_jspace_records'))->loadResult());
	}
	
    /**
     * @expectedException RuntimeException
     */
	public function testSingleCategoryInvalidURL()
	{
		$category = $this->categories[0];
		
		$harvester = new JSpaceIngestionOAIHarvester($category);
		$harvester->harvest()->ingest();
	}
	
	public function testSingleCategoryBadResumptionToken()
	{
		$category = $this->categories[0];
		
		try
		{
			$harvester = new JSpaceIngestionOAIHarvester($category);
			$harvester->harvest()->ingest();
		}
		catch (Exception $e)
		{
			$this->assertEquals('Unknown metadata format', $e->getMessage());
		}
		
		$query = JFactory::getDbo()->getQuery(true);
		$harvest = JFactory::getDbo()->setQuery($query->select(array('catid', 'harvested', 'resumptionToken', 'failures'))->from('jos_jspace_harvests')->where('catid=10'))->loadObject('stdClass');
		
		$this->assertNull($harvest);
	}
	
	public function testSingleCategoryWithAssets()
	{
		$category = $this->categories[1];
		
		$harvester = new JSpaceIngestionOAIAssetHarvester($category);
		$harvester->harvest()->ingest();
		
		$query = JFactory::getDbo()->getQuery(true);
		$harvest = JFactory::getDbo()->setQuery($query->select('count(*)')->from('jos_jspace_assets'))->loadResult();
		
		echo 'harvested assets='.$harvest;
		
		$query = JFactory::getDbo()->getQuery(true);
		$this->assertEquals(306, JFactory::getDbo()->setQuery($query->select('count(*)')->from('jos_jspace_records'))->loadResult());
	}
	
	public function testCategoryClean()
	{
		$category = $this->categories[1];
		
		$harvester = new JSpaceIngestionOAIHarvester($category);
		$harvester->harvest();
		$harvester->rollback();
		
		$query = JFactory::getDbo()->getQuery(true);
		$this->assertEquals(0, JFactory::getDbo()->setQuery($query->select('count(*)')->from('jos_jspace_cache'))->loadResult());
		
		$query = JFactory::getDbo()->getQuery(true);
		$category = JFactory::getDbo()->setQuery($query->select(array('id', 'params'))->from('jos_categories')->where('id=10'))->loadObject('stdClass');
		
		$params = new JRegistry();
		$params->loadString($category->params);
		
		echo $params->get('oai_harvested');
	}
	
	public function testCategoryReset()
	{
		$category = $this->categories[1];
		
		$harvester = new JSpaceIngestionOAIHarvester($category);
		$harvester->harvest();
		$harvester->reset();
		
		$query = JFactory::getDbo()->getQuery(true);
		$this->assertEquals(0, JFactory::getDbo()->setQuery($query->select('count(*)')->from('jos_jspace_cache'))->loadResult());
		
		$query = JFactory::getDbo()->getQuery(true);
		$category = JFactory::getDbo()->setQuery($query->select(array('id', 'params'))->from('jos_categories')->where('id=10'))->loadObject('stdClass');
		
		$params = new JRegistry();
		$params->loadString($category->params);
		
		$this->assertNull($params->get('oai_harvested'));
	}
	
    public function getConnection()
    {
		if (!is_null(self::$driver))
		{
			return $this->createDefaultDBConnection(self::$driver->getConnection(), ':memory:');
		}
		else
		{
			return null;
		}
    }
	
	protected function getDataSet()
	{
		$categories = 'jos_categories.csv';
		
		$dataSet = new PHPUnit_Extensions_Database_DataSet_CsvDataSet(',', "'", '\\');
		
		if ($this->getName() == 'testSingleCategoryInvalidURL')
		{
			$categories = 'jos_categories_invalidurl.csv';
		}
		
		if ($this->getName() == 'testSingleCategoryBadResumptionToken')
		{
			$categories = 'jos_categories_badresumptiontoken.csv';
		}
		
		$dataSet->addTable('jos_extensions', JSPACEPATH_TESTS.'/stubs/database/jos_extensions.csv');
		$dataSet->addTable('jos_categories', JSPACEPATH_TESTS.'/stubs/database/'.$categories);
		$dataSet->addTable('jos_usergroups', JSPACEPATH_TESTS.'/stubs/database/jos_usergroups.csv');
		$dataSet->addTable('jos_viewlevels', JSPACEPATH_TESTS.'/stubs/database/jos_viewlevels.csv');
		$dataSet->addTable('jos_jspace_records', JSPACEPATH_TESTS.'/stubs/database/jos_jspace_records.csv');
		$dataSet->addTable('jos_jspace_record_ancestors', JSPACEPATH_TESTS.'/stubs/database/jos_jspace_record_ancestors.csv');
		$dataSet->addTable('jos_jspace_record_categories', JSPACEPATH_TESTS.'/stubs/database/jos_jspace_record_categories.csv');
		$dataSet->addTable('jos_jspace_assets', JSPACEPATH_TESTS.'/stubs/database/jos_jspace_assets.csv');
		$dataSet->addTable('jos_jspace_cache', JSPACEPATH_TESTS.'/stubs/database/jos_jspace_cache.csv');
		$dataSet->addTable('jos_assets', JSPACEPATH_TESTS.'/stubs/database/jos_assets.csv');
		$dataSet->addTable('jos_content_types', JSPACEPATH_TESTS.'/stubs/database/jos_content_types.csv');
		$dataSet->addTable('jos_ucm_history', JSPACEPATH_TESTS.'/stubs/database/jos_ucm_history.csv');
		$dataSet->addTable('jos_tags', JSPACEPATH_TESTS.'/stubs/database/jos_tags.csv');
		$dataSet->addTable('jos_contentitem_tag_map', JSPACEPATH_TESTS.'/stubs/database/jos_contentitem_tag_map.csv');
		$dataSet->addTable('jos_ucm_base', JSPACEPATH_TESTS.'/stubs/database/jos_ucm_base.csv');

		return $dataSet;
	}
	
	public static function tearDownAfterClass()
	{
		JFactory::$database = self::$_stash;
		self::$driver = null;
	}
}