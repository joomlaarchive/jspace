<?php
require_once(JSPACEPATH_TESTS.'/core/case/database.php');

use JSpace\Ingestion\Harvest;
use \JEventDispatcher;
use \JPluginHelper;
use \JFactory;
use Joomla\Registry\Registry;

// @todo query dspace for info to assert tests against.
class HarvestTest extends \TestCaseDatabase
{
    public function testDiscoveryOAI()
    {
        $url = 'http://localhost/jspace/oai/request.php';

        $dispatcher = JEventDispatcher::getInstance();
        JPluginHelper::importPlugin('jspace');
        $result = $dispatcher->trigger('onJSpaceHarvestDiscover', array($url));

        $expected = new Registry;
        $expected->set('discovery.type', 'oai');
        $expected->set('discovery.url', 'http://localhost/jspace/oai/request.php');
        $expected->set('discovery.plugin.metadata', 'qdc');
        $expected->set('discovery.plugin.assets', 'ore');

        $this->assertEquals($expected, $result[0]);
    }

    public function testDiscoveryOpenSearch()
    {
        $url = 'http://localhost/jspace/opensearch/simple-search.html?query=Ebola';

        $dispatcher = JEventDispatcher::getInstance();
        JPluginHelper::importPlugin('jspace');
        $result = $dispatcher->trigger('onJSpaceHarvestDiscover', array($url));

        $expected = new Registry;
        $expected->set('discovery.type', 'opensearch');
        $expected->set('discovery.url', 'http://localhost/jspace/opensearch/atom.xml?query=Ebola&start={startIndex?}&rpp={count?}');
        $expected->set('discovery.plugin.type', 'application/atom+xml');

        $this->assertEquals($expected, $result[1]);
    }

    public function testHarvestOpenSearch()
    {
        $params = new Registry;
        $params->set('discovery.type', 'opensearch');
        $params->set('discovery.url', 'http://localhost/jspace/opensearch/atom.xml?query=joomla');
        $params->set('discovery.plugin.type', 'application/atom+xml');

        $data = array(
            'originating_url'=>'http://localhost/jspace/opensearch/atom.xml?query=joomla',
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

        $harvest = new Harvest();
        $harvest->bind($data);
        $harvest->set('params', $params);
        $harvest->save();

        $dispatcher = JEventDispatcher::getInstance();
        JPluginHelper::importPlugin('jspace', 'harvest', true);
        $dispatcher->trigger('onJSpaceExecuteCliCommand');

        $harvest->load($harvest->id);

        $query = JFactory::getDbo()->getQuery(true);
        $query->select("COUNT(*)")->from('#__jspace_records')->where('alias <> \'root\'');

        $this->assertEquals(4, (int)JFactory::getDbo()->setQuery($query)->loadResult());
    }

    public function testDiscoverOpenSearchInvalidUrl()
    {
        $url = "http://localhost/jspace/opensearch/invalid.xml?query=joomla";

        $dispatcher = JEventDispatcher::getInstance();
        JPluginHelper::importPlugin('jspace', 'opensearch');

        $result = $dispatcher->trigger('onJSpaceHarvestDiscover', array($url));

        $this->assertFalse($result[0]);
    }

    public function testHarvestMultipleTimes()
    {
        $params = new Registry;
        $params->set('discovery.type', 'oai');
        $params->set('discovery.url', 'http://localhost/jspace/oai/request.php');
        $params->set('discovery.plugin.metadata', 'qdc');
        $params->set('discovery.plugin.assets', 'ore');
        $params->set('set', 'com_10673_1');

        $data = array(
            'originating_url'=>'http://localhost/jspace/oai/request.php',
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

        $harvest = new Harvest();
        $harvest->bind($data);
        $harvest->set('params', $params);
        $harvest->save();

        $dispatcher = JEventDispatcher::getInstance();
        JPluginHelper::importPlugin('content', 'harvest', true);
        $dispatcher->trigger('onJSpaceExecuteCliCommand');

        $query = JFactory::getDbo()->getQuery(true);
        $query->select("COUNT(*)")->from('#__jspace_records')->where('alias <> \'root\'');

        $this->assertEquals(2, (int)JFactory::getDbo()->setQuery($query)->loadResult());

        $dispatcher = JEventDispatcher::getInstance();
        JPluginHelper::importPlugin('content', 'harvest', true);
        $dispatcher->trigger('onJSpaceExecuteCliCommand');

        $query = JFactory::getDbo()->getQuery(true);
        $query->select("COUNT(*)")->from('#__jspace_records')->where('alias <> \'root\'');

        $this->assertEquals(2, (int)JFactory::getDbo()->setQuery($query)->loadResult());
    }
}