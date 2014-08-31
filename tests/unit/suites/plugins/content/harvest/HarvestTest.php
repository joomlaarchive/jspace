<?php
require_once(JSPACEPATH_TESTS.'/core/case/database.php');

jimport('jspace.ingestion.harvest');

// @todo query dspace for info to assert tests against.
class HarvestTest extends TestCaseDatabase
{
    public function testDiscoveryOAI()
    {
        $url = 'http://demo.dspace.org/oai/request';
    
        $dispatcher = JEventDispatcher::getInstance();
        JPluginHelper::importPlugin('jspace');
        $result = $dispatcher->trigger('onJSpaceHarvestDiscover', array($url));

        $expected = new JRegistry;
        $expected->set('discovery.type', 'oai');
        $expected->set('discovery.url', 'http://demo.dspace.org/oai/request');
        $expected->set('discovery.plugin.metadata', 'qdc');
        $expected->set('discovery.plugin.assets', 'ore');
        
        $this->assertEquals($expected, $result[0]);
    }
    
    public function testDiscoveryOpenSearch()
    {
        $url = 'http://apps.who.int/iris/simple-search?query=Ebola';
    
        $dispatcher = JEventDispatcher::getInstance();
        JPluginHelper::importPlugin('jspace');
        $result = $dispatcher->trigger('onJSpaceHarvestDiscover', array($url));

        $expected = new JRegistry;
        $expected->set('discovery.type', 'opensearch');
        $expected->set('discovery.url', 'http://apps.who.int//iris/open-search/?query=Ebola&start={startIndex?}&rpp={count?}&format=atom');
        $expected->set('discovery.plugin.type', 'application/atom+xml');
        
        $this->assertEquals($expected, $result[1]);
    }

    public function testHarvestOpenSearch()
    {
        $params = new JRegistry;
        $params->set('discovery.type', 'opensearch');
        $params->set('discovery.url', 'http://apps.who.int//iris/open-search/?query=Ebola&start={startIndex?}&rpp={count?}&format=atom');
        $params->set('discovery.plugin.type', 'application/atom+xml');
    
        $data = array(
            'originating_url'=>'http://apps.who.int/iris/simple-search?query=Ebola',
            'harvester'=>0,
            'harvested'=>'0000-00-00 00:00:00',
            'frequency'=>1,
            'total'=>0,
            'params'=>json_encode(array()),
            'state'=>1,
            'created'=>JFactory::getDate()->toSql(),
            'created_by'=>525,
            'catid'=>1,
            'state_default'=>1,
            'language_default'=>'*',
            'access_default'=>0
        );
        
        $harvest = new JSpaceIngestionHarvest();
        $harvest->bind($data);
        $harvest->set('params', $params);
        $harvest->save();
        
        $dispatcher = JEventDispatcher::getInstance();
        JPluginHelper::importPlugin('content', 'harvest', true);
        $dispatcher->trigger('onJSpaceExecuteCliCommand');
        
        $query = JFactory::getDbo()->getQuery(true);
        $query->select("COUNT(*)")->from('#__jspace_records')->where('alias <> \'root\'');

        $results = JFactory::getDbo()->setQuery($query)->loadObjectList();
    }
    
    public function testHarvestMultipleTimes()
    {
        $params = new JRegistry;
        $params->set('discovery.type', 'oai');
        $params->set('discovery.url', 'http://demo.dspace.org/oai/request');
        $params->set('discovery.plugin.metadata', 'qdc');
        $params->set('discovery.plugin.assets', 'ore');
        $params->set('set', 'com_10673_1');
    
        $data = array(
            'originating_url'=>'http://demo.dspace.org/oai/request',
            'harvester'=>0,
            'harvested'=>'0000-00-00 00:00:00',
            'frequency'=>0,
            'total'=>0,
            'params'=>json_encode(array()),
            'state'=>1,
            'created'=>JFactory::getDate()->toSql(),
            'created_by'=>525,
            'catid'=>1
        );
        
        $harvest = new JSpaceIngestionHarvest();
        $harvest->bind($data);
        $harvest->set('params', $params);
        $harvest->save();
        
        $dispatcher = JEventDispatcher::getInstance();
        JPluginHelper::importPlugin('content', 'harvest', true);
        $dispatcher->trigger('onJSpaceExecuteCliCommand');
        
        $query = JFactory::getDbo()->getQuery(true);
        $query->select("COUNT(*)")->from('#__jspace_records')->where('alias <> \'root\'');

        $this->assertEquals(5, (int)JFactory::getDbo()->setQuery($query)->loadResult());
        
        $dispatcher = JEventDispatcher::getInstance();
        JPluginHelper::importPlugin('content', 'harvest', true);
        $dispatcher->trigger('onJSpaceExecuteCliCommand');

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