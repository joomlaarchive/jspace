<?php
jimport('jspace.ingestion.harvest');

require_once(JSPACEPATH_TESTS.'/core/case/database.php');

// @todo query dspace for info to assert tests against.
class OpenSearchTest extends TestCaseDatabase
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
            'originating_url'=>'http://apps.who.int/iris/simple-search?query=Ebola',
            'harvester'=>0,
            'frequency'=>1,
            'params'=>json_encode(array()),
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

        JPluginHelper::importPlugin('jspace', 'opensearch', true, $dispatcher);
        
        $result = $dispatcher->trigger('onJSpaceHarvestDiscover', array($harvest->originating_url));

        $expected = new JRegistry;
        $expected->set('discovery.type', 'opensearch');
        $expected->set('discovery.url', 'http://apps.who.int//iris/open-search/?query=Ebola&start={startIndex?}&rpp={count?}&format=atom');
        $expected->set('discovery.plugin.type', 'application/atom+xml');
        
        $this->assertEquals($expected, $result[0]);
    }
    
    public function testOnJSpaceHarvestDiscoverDirect()
    {
        $data = $this->data;
        $data['url'] = 'http://apps.who.int//iris/open-search/?query=Ebola&start={startIndex?}&rpp={count?}&format=atom';
    
        $harvest = JSpaceIngestionHarvest::getInstance();
        $harvest->bind($data);
    
        $dispatcher = JEventDispatcher::getInstance();

        JPluginHelper::importPlugin('jspace', 'opensearch', true, $dispatcher);
        
        $result = $dispatcher->trigger('onJSpaceHarvestDiscover', array($harvest->originating_url));

        $expected = new JRegistry;
        $expected->set('discovery.type', 'opensearch');
        $expected->set('discovery.url', 'http://apps.who.int//iris/open-search/?query=Ebola&start={startIndex?}&rpp={count?}&format=atom');
        $expected->set('discovery.plugin.type', 'application/atom+xml');
        
        $this->assertEquals($expected, $result[0]);    
    }

    public function testOnJSpaceHarvestDiscoverInvalidUrl()
    {
        $data = $this->data;
        $data['originating_url'] = 'http://archive.demo2.knowledgearc.net/opensearch/?query=test';
    
        $harvest = JSpaceIngestionHarvest::getInstance();
        $harvest->bind($data);
    
        $dispatcher = JEventDispatcher::getInstance();

        JPluginHelper::importPlugin('jspace', 'opensearch', true, $dispatcher);
        
        $result = $dispatcher->trigger('onJSpaceHarvestDiscover', array($harvest->originating_url));

        $this->assertFalse($result[0]);
    }
    
    public function testOnJSpaceHarvestRetrieve()
    {
        $harvest = JSpaceIngestionHarvest::getInstance();
        $harvest->bind($this->data);
        $harvest->originating_url = 'http://archive.demo2.knowledgearc.net/open-search/?query=joomla';
        $harvest->get('params')->set('discovery.type', 'opensearch');
        $harvest->get('params')->set('discovery.url', 'http://archive.demo2.knowledgearc.net/open-search/?query=joomla');
        $harvest->get('params')->set('discovery.plugin.type', 'application/atom+xml');
    
        $dispatcher = JEventDispatcher::getInstance();

        JPluginHelper::importPlugin('jspace', 'opensearch', true, $dispatcher);

        $dispatcher->trigger('onJSpaceHarvestRetrieve', array($harvest));
        
        $query = JFactory::getDbo()->getQuery(true);
        $query->select("COUNT(*)")->from('#__jspace_cache')->where('harvest_id='.$this->data['id']);
        
        $this->assertEquals(1, (int)JFactory::getDbo()->setQuery($query)->loadResult());
    }
    
    public function testOnJSpaceHarvestRetrieveRSS()
    {
        $data = $this->data;
        $data['originating_url'] = 'http://apps.who.int/iris/open-search/?query=Ebola&start={startIndex?}&rpp={count?}&format=rss';
        
        $harvest = JSpaceIngestionHarvest::getInstance();
        $harvest->bind($data);
        $harvest->get('params')->set('discovery.type', 'opensearch');
        $harvest->get('params')->set('discovery.url', 'http://apps.who.int/iris/open-search/?query=Ebola&start={startIndex?}&rpp={count?}&format=rss');
        $harvest->get('params')->set('discovery.plugin.type', 'application/rss+xml');
        
    
        $dispatcher = JEventDispatcher::getInstance();

        JPluginHelper::importPlugin('jspace', 'opensearch', true, $dispatcher);

        $dispatcher->trigger('onJSpaceHarvestRetrieve', array($harvest));
        
        $query = JFactory::getDbo()->getQuery(true);
        $query->select("COUNT(*)")->from('#__jspace_cache')->where('harvest_id='.$data['id']);
        
        $this->assertEquals(752, (int)JFactory::getDbo()->setQuery($query)->loadResult());
    }
    
    public function testOnJSpaceHarvestIngest()
    {
        $harvest = JSpaceIngestionHarvest::getInstance();
        $harvest->bind($this->data);
        $harvest->originating_url = 'http://archive.demo2.knowledgearc.net/open-search/?query=joomla';
        $harvest->get('params')->set('discovery.type', 'opensearch');
        $harvest->get('params')->set('discovery.url', 'http://archive.demo2.knowledgearc.net/open-search/?query=joomla');
        $harvest->get('params')->set('discovery.plugin.type', 'application/atom+xml');
        
        $dispatcher = JEventDispatcher::getInstance();

        JPluginHelper::importPlugin('jspace', 'opensearch', true, $dispatcher);
        
        $dispatcher->trigger('onJSpaceHarvestRetrieve', array($harvest));
        
        $dispatcher->trigger('onJSpaceHarvestIngest', array($harvest));
        
        $query = JFactory::getDbo()->getQuery(true);
        $query->select("COUNT(*)")->from('#__jspace_records')->where('alias <> \'root\';');
        
        $this->assertEquals(1, (int)JFactory::getDbo()->setQuery($query)->loadResult());
    }
    
    protected function getDataSet()
    {
        $dataset = parent::getDataSet();
        $dataset->addTable('jos_extensions', JSPACEPATH_TESTS.'/stubs/database/jos_extensions_no_storage.csv');
        
        return $dataset;
    }
}