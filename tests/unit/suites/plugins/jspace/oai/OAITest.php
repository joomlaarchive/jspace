<?php
jimport('jspace.ingestion.harvest');

require_once(JSPACEPATH_TESTS.'/core/case/database.php');

// @todo query dspace for info to assert tests against.
class OAITest extends TestCaseDatabase
{
    public $data;
    
    public function setUp()
    {
        parent::setUp();
        
        $registry = new JRegistry;
        $registry->set('harvest_assets', false);
        $registry->set('default.access', '0');
        $registry->set('default.language', '*');
        $registry->set('default.state', 1);
        
        $this->data = array(
            'id'=>1,
            'originating_url'=>'http://demo.dspace.org/oai/request',
            'harvester'=>0,
            'frequency'=>1,
            'harvested'=>'0000-00-00 00:00:00',
            'params'=>json_encode($registry->toArray()),
            'state'=>1,
            'created'=>JFactory::getDate()->toSql(),
            'created_by'=>525,
            'catid'=>1
        );
    }

    public function testOnJSpaceHarvestDiscover()
    {
        $harvest = JSpaceIngestionHarvest::getInstance();
        $harvest->bind($this->data);
    
        $dispatcher = JEventDispatcher::getInstance();

        JPluginHelper::importPlugin('jspace', 'oai', true, $dispatcher);
        
        $result = $dispatcher->trigger('onJSpaceHarvestDiscover', array($harvest->originating_url));

        $expected = new JRegistry;
        $expected->set('discovery.type', 'oai');
        $expected->set('discovery.url', 'http://demo.dspace.org/oai/request');
        $expected->set('discovery.plugin.metadata', 'qdc');
        $expected->set('discovery.plugin.assets', 'ore');
        
        $this->assertEquals($expected, $result[0]);
    }

    public function testOnJSpaceHarvestDiscoverInvalidUrl()
    {
        $data = $this->data;
        $data['originating_url'] = 'http://apps.who.int/iris/simple-search?query=Ebola';
    
        $harvest = JSpaceIngestionHarvest::getInstance();
        $harvest->bind($data);
    
        $dispatcher = JEventDispatcher::getInstance();

        JPluginHelper::importPlugin('jspace', 'oai', true, $dispatcher);
        
        $result = $dispatcher->trigger('onJSpaceHarvestDiscover', array($harvest->originating_url));

        $this->assertFalse($result[0]);
    }
    
    public function testOnJSpaceHarvestRetrieve()
    {
        $harvest = JSpaceIngestionHarvest::getInstance();
        $harvest->bind($this->data);
    
        $dispatcher = JEventDispatcher::getInstance();

        JPluginHelper::importPlugin('jspace', 'oai', true, $dispatcher);
        
        $result = $dispatcher->trigger('onJSpaceHarvestDiscover', array($harvest->originating_url));

        $harvest->get('params')->loadArray($result[0]->toArray());

        $dispatcher->trigger('onJSpaceHarvestRetrieve', array($harvest));
        
        $query = JFactory::getDbo()->getQuery(true);
        
        $query->select("COUNT(*)")->from('#__jspace_cache')->where('harvest_id='.$this->data['id']);
        
        $this->assertEquals(30, (int)JFactory::getDbo()->setQuery($query)->loadResult());
    }
    
    public function testOnJSpaceHarvestIngest()
    {
        $harvest = JSpaceIngestionHarvest::getInstance();
        $harvest->bind($this->data);
    
        $dispatcher = JEventDispatcher::getInstance();

        JPluginHelper::importPlugin('jspace', 'oai', true, $dispatcher);
        
        $result = $dispatcher->trigger('onJSpaceHarvestDiscover', array($harvest->originating_url));

        $harvest->get('params')->loadArray($result[0]->toArray());

        $dispatcher->trigger('onJSpaceHarvestRetrieve', array($harvest));
        
        $dispatcher->trigger('onJSpaceHarvestIngest', array($harvest));
        
        $query = JFactory::getDbo()->getQuery(true);
        $query->select("COUNT(*)")->from('#__jspace_records')->where('alias <> \'root\'');
        
        $this->assertEquals(30, (int)JFactory::getDbo()->setQuery($query)->loadResult());
    }
    
    public function testOnJSpaceHarvestIngestSingleSet()
    {
        $harvest = JSpaceIngestionHarvest::getInstance();
        $harvest->bind($this->data);
        $harvest->get('params')->set('harvest_type', 0);
        $harvest->get('params')->set('set', 'com_10673_1');
        
        $dispatcher = JEventDispatcher::getInstance();

        JPluginHelper::importPlugin('jspace', 'oai', true, $dispatcher);
        
        $result = $dispatcher->trigger('onJSpaceHarvestDiscover', array($harvest->originating_url));

        $harvest->get('params')->loadArray($result[0]->toArray());

        $dispatcher->trigger('onJSpaceHarvestRetrieve', array($harvest));
        
        $dispatcher->trigger('onJSpaceHarvestIngest', array($harvest));
        
        $query = JFactory::getDbo()->getQuery(true);
        $query->select("COUNT(*)")->from('#__jspace_records')->where('alias <> \'root\'');
        
        $this->assertEquals(5, (int)JFactory::getDbo()->setQuery($query)->loadResult());
    }

    public function testOnJSpaceHarvestIngestWithWeblinks()
    {
        $harvest = JSpaceIngestionHarvest::getInstance();
        $harvest->bind($this->data);
        $harvest->get('params')->set('harvest_type', 1);
        
        $dispatcher = JEventDispatcher::getInstance();

        JPluginHelper::importPlugin('jspace', 'oai', true, $dispatcher);
        
        $result = $dispatcher->trigger('onJSpaceHarvestDiscover', array($harvest->originating_url));

        $harvest->get('params')->loadArray($result[0]->toArray());

        $dispatcher->trigger('onJSpaceHarvestRetrieve', array($harvest));
        
        $dispatcher->trigger('onJSpaceHarvestIngest', array($harvest));
        
        $query = JFactory::getDbo()->getQuery(true);
        $query->select("COUNT(*)")->from('#__jspace_records')->where('alias <> \'root\'');
        
        $this->assertEquals(30, (int)JFactory::getDbo()->setQuery($query)->loadResult());
        
        $query = JFactory::getDbo()->getQuery(true);
        $query->select("COUNT(*)")->from('#__weblinks AS a')->join('inner', '#__jspace_references AS b ON a.id = b.id');

        $this->assertEquals(36, (int)JFactory::getDbo()->setQuery($query)->loadResult());
    }
    
    public function testOnJSpaceHarvestIngestWithAssets()
    {
        $harvest = JSpaceIngestionHarvest::getInstance();
        $harvest->bind($this->data);
        $harvest->get('params')->set('harvest_type', 2);
        
        $dispatcher = JEventDispatcher::getInstance();

        JPluginHelper::importPlugin('jspace', 'oai', true, $dispatcher);
        
        $result = $dispatcher->trigger('onJSpaceHarvestDiscover', array($harvest->originating_url));

        $harvest->get('params')->loadArray($result[0]->toArray());

        $dispatcher->trigger('onJSpaceHarvestRetrieve', array($harvest));
        
        $dispatcher->trigger('onJSpaceHarvestIngest', array($harvest));
        
        $query = JFactory::getDbo()->getQuery(true);
        $query->select("COUNT(*)")->from('#__jspace_records')->where('alias <> \'root\'');
        
        $this->assertEquals(30, (int)JFactory::getDbo()->setQuery($query)->loadResult());
        
        $query = JFactory::getDbo()->getQuery(true);
        $query->select("COUNT(*)")->from('#__jspace_assets');
        
        $this->assertEquals(36, (int)JFactory::getDbo()->setQuery($query)->loadResult());
    }
    
    public function testOnJSpaceHarvestWithDuplicateCacheData()
    {
        $harvest = JSpaceIngestionHarvest::getInstance();
        $harvest->bind($this->data);
        $harvest->get('params')->set('set', 'com_10673_1');
        
        $dispatcher = JEventDispatcher::getInstance();

        JPluginHelper::importPlugin('jspace', 'oai', true, $dispatcher);
        
        $result = $dispatcher->trigger('onJSpaceHarvestDiscover', array($harvest->originating_url));

        $harvest->get('params')->loadArray($result[0]->toArray());

        $dispatcher->trigger('onJSpaceHarvestRetrieve', array($harvest));
        
        // harvest again. We should get shouldn't get duplicates.
        $dispatcher->trigger('onJSpaceHarvestRetrieve', array($harvest));
        
        $dispatcher->trigger('onJSpaceHarvestIngest', array($harvest));
        
        $query = JFactory::getDbo()->getQuery(true);
        $query->select("COUNT(*)")->from('#__jspace_records')->where('alias <> \'root\'');
        
        $this->assertEquals(5, (int)JFactory::getDbo()->setQuery($query)->loadResult());
    }
    
    protected function getDataSet()
    {
        $dataset = parent::getDataSet();
        $dataset->addTable('jos_extensions', JSPACEPATH_TESTS.'/stubs/database/jos_extensions_no_storage.csv');
        
        return $dataset;
    }
}