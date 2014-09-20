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
        $registry->set('default.schema', 'record');
        $registry->set('default.access', '0');
        $registry->set('default.language', '*');
        $registry->set('default.state', 1);
        
        $this->data = array(
            'id'=>1,
            'originating_url'=>'http://localhost/jspace/request.php',
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
        $expected->set('discovery.url', 'http://localhost/jspace/request.php');
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
    
    public function testOnJSpaceHarvestRetrieveQDC()
    {
        $harvest = JSpaceIngestionHarvest::getInstance();
        $harvest->bind($this->data);
        $harvest->get('params')->set('harvest_type', 0);
        $harvest->get('params')->set('discovery.type', 'oai');
        $harvest->get('params')->set('discovery.url', 'http://localhost/jspace/request.php');
        $harvest->get('params')->set('discovery.plugin.metadata', 'qdc');
    
        $dispatcher = JEventDispatcher::getInstance();
        JPluginHelper::importPlugin('jspace', 'oai', true, $dispatcher);

        $dispatcher->trigger('onJSpaceHarvestRetrieve', array($harvest));
        
        $query = JFactory::getDbo()->getQuery(true);
        
        $query->select("COUNT(*)")->from('#__jspace_cache')->where('harvest_id='.$this->data['id']);
        
        // check number of records digested.
        $this->assertEquals(200, (int)JFactory::getDbo()->setQuery($query)->loadResult());

        $query = JFactory::getDbo()->getQuery(true);
        
        $query
            ->select("data")
            ->from('#__jspace_cache')
            ->where('id='.JFactory::getDbo()->q('oai:archive.bora.wijiti.net:10049/286'));
    
        // Retrieve a single cached item and check it for integrity.
        $expected = '{"metadata":{"dc":{"title":["Hjemmebes\u00f8k til familier med nyf\u00f8dt barn : rapport fra kartlegging av helses\u00f8sters tilbud ved helsestasjoner i Bergen"],"creator":["\u00d8kland, Toril","Hj\u00e4lmhult, Esther"],"type":["Report"],"identifier":["http:\/\/hdl.handle.net\/10049\/286"],"language":["nob"]},"dcterms":{"dateAccepted":["2010-11-18T10:11:42Z"],"available":["2010-11-18T10:11:42Z"],"created":["2010-11-18T10:11:42Z"],"issued":["2010-11-18T10:11:42Z"]}}}';
        
        $this->assertEquals($expected, JFactory::getDbo()->setQuery($query)->loadResult());
    }
    
    public function testOnJSpaceHarvestRetrieveOAI_DC()
    {
        $harvest = JSpaceIngestionHarvest::getInstance();
        $harvest->bind($this->data);
        $harvest->get('params')->set('harvest_type', 0);
        $harvest->get('params')->set('discovery.type', 'oai');
        $harvest->get('params')->set('discovery.url', 'http://localhost/jspace/request.php');
        $harvest->get('params')->set('discovery.plugin.metadata', 'oai_dc');
    
        $dispatcher = JEventDispatcher::getInstance();
        JPluginHelper::importPlugin('jspace', 'oai', true, $dispatcher);

        $dispatcher->trigger('onJSpaceHarvestRetrieve', array($harvest));
        
        $query = JFactory::getDbo()->getQuery(true);
        
        $query->select("COUNT(*)")->from('#__jspace_cache')->where('harvest_id='.$harvest->id);
        
        // check number of records digested.
        $this->assertEquals(200, (int)JFactory::getDbo()->setQuery($query)->loadResult());
        
        $query = JFactory::getDbo()->getQuery(true);
        
        $query
            ->select("data")
            ->from('#__jspace_cache')
            ->where('id='.JFactory::getDbo()->q('oai:archive.bora.wijiti.net:10049/286'));
    
        // Retrieve a single cached item and check it for integrity.
        $expected = '{"metadata":{"dc":{"title":["Hjemmebes\u00f8k til familier med nyf\u00f8dt barn : rapport fra kartlegging av helses\u00f8sters tilbud ved helsestasjoner i Bergen"],"creator":["\u00d8kland, Toril","Hj\u00e4lmhult, Esther"],"description":["Hensikten med prosjektet har v\u00e6rt \u00e5 utvikle og styrke praksis gjennom \u00e5 dokumentere kunnskap om helses\u00f8sters hjemmebes\u00f8k til foreldre med nyf\u00f8dte barn. I f\u00f8lge sentrale forskrifter og retningslinjer skal hjemmebes\u00f8k tilbys foreldre med nyf\u00f8dt barn, helst innen to uker etter f\u00f8dsel. Siden midten av 1990-\u00e5rene tyder antall hjemmebes\u00f8k p\u00e5 \u00e5 reduseres i en del kommuner og bydeler, samtidig som mor og barn ofte utskrives tidlig fra f\u00f8deinstitusjon. Oppsummering av eksisterende forskning omkring hjemmebes\u00f8k tydeliggj\u00f8r at Norden er spesiell med et universelt tilbud. Forskningen er sparsom, men gir likevel viktig bidrag til \u00e5 synliggj\u00f8re, problematisere og utvikle god praksis for \u00e5 kunne ta velinformerte beslutninger.\r\nProblemstilling. Hvordan vurderer og vektlegger helses\u00f8stre sin praksis omkring hjemmebes\u00f8kstilbudet?\r\nMetodisk tiln\u00e6rming. I prosjektet kartlegges helses\u00f8stres vurderinger og vektlegging av hjemmebes\u00f8k til nyblivne foreldre i Bergen. Datainnsamling med sp\u00f8rreskjema til 82 helses\u00f8stre er gjennomf\u00f8rt 2007 med svarprosent p\u00e5 60. Data er statistisk bearbeidet med kommunens dataprogramsystem Corporator. \u00c5pne undersp\u00f8rsm\u00e5l er kvalitativt bearbeidet og analysert.\r\nFunn. Helses\u00f8strene mener det er stort behov for hjemmebes\u00f8k, og med noen unntak omkring premature barn og barselkvinnen kjenner de seg godt kompetente til oppgaven. Unders\u00f8kelsen viser likevel at foreldre med nyf\u00f8dt barn i Bergen i ulik grad f\u00e5r dette tilbudet. Ved tidspress prioriterer en del helses\u00f8stre hjemmebes\u00f8k kun til f\u00f8rstegangsf\u00f8dende. Svarene tyder ogs\u00e5 p\u00e5 at noen med en egendefinert praksis ytterligere nedprioriter oppgaven og avviker fra offentlige anbefalinger. M\u00e5ten helses\u00f8ster presenterer tilbudet p\u00e5, kan spille en avgj\u00f8rende rolle for om foreldre takker ja til hjemmebes\u00f8k. Dersom hjemmebes\u00f8ket utelates, er det ikke n\u00f8dvendigvis en tidsbesparelse, fordi foreldre ofte kompenserer med hyppigere konsultasjoner p\u00e5 helsestasjonen.\r\nKonklusjon. Unders\u00f8kelsen viser noen \u00e5rsaker til ujevn hjemmebes\u00f8ksdekning og ulikt helsetjenestetilbud til nyblivne foreldre i Bergen. Det kan v\u00e6re aktuelt for noen \u00e5 videreutvikle sin faglige kompetanse i forhold til premature barn og deres familier."],"date":["2010-11-18T10:11:42Z","2010-11-18T10:11:42Z","2010-11-18T10:11:42Z"],"type":["Report"],"identifier":["http:\/\/hdl.handle.net\/10049\/286"],"language":["nob"]}}}';
        
        $this->assertEquals($expected, JFactory::getDbo()->setQuery($query)->loadResult());
    }
    
    public function testOnJSpaceHarvestIngest()
    {
        $harvest = JSpaceIngestionHarvest::getInstance();
        $harvest->bind($this->data);
        $harvest->get('params')->set('harvest_type', 0);
        $harvest->get('params')->set('discovery.type', 'oai');
        $harvest->get('params')->set('discovery.url', 'http://localhost/jspace/request.php');
        $harvest->get('params')->set('discovery.plugin.metadata', 'oai_dc');
    
        $dispatcher = JEventDispatcher::getInstance();
        JPluginHelper::importPlugin('jspace', 'oai', true, $dispatcher);

        $dispatcher->trigger('onJSpaceHarvestRetrieve', array($harvest));
        
        $dispatcher->trigger('onJSpaceHarvestIngest', array($harvest));
        
        $query = JFactory::getDbo()->getQuery(true);
        $query->select("COUNT(*)")->from('#__jspace_records')->where('alias <> \'root\'');
        
        $this->assertEquals(200, (int)JFactory::getDbo()->setQuery($query)->loadResult());
    }
    
    public function testOnJSpaceHarvestIngestSingleSet()
    {
        $harvest = JSpaceIngestionHarvest::getInstance();
        $harvest->bind($this->data);
        $harvest->get('params')->set('harvest_type', 0);
        $harvest->get('params')->set('set', 'com_10049_24');
        
        $dispatcher = JEventDispatcher::getInstance();

        JPluginHelper::importPlugin('jspace', 'oai', true, $dispatcher);
        
        $result = $dispatcher->trigger('onJSpaceHarvestDiscover', array($harvest->originating_url));

        $harvest->get('params')->loadArray($result[0]->toArray());

        $dispatcher->trigger('onJSpaceHarvestRetrieve', array($harvest));
        
        $dispatcher->trigger('onJSpaceHarvestIngest', array($harvest));
        
        $query = JFactory::getDbo()->getQuery(true);
        $query->select("COUNT(*)")->from('#__jspace_records')->where('alias <> \'root\'');
        
        $this->assertEquals(33, (int)JFactory::getDbo()->setQuery($query)->loadResult());
        
        $query = JFactory::getDbo()->getQuery(true);
        $query
            ->select("id")
            ->from('#__jspace_records')
            ->order('id DESC');
            
        $record = JSpaceRecord::getInstance((int)JFactory::getDbo()->setQuery($query, 0, 1)->loadResult());
        
        $this->assertEquals('record', $record->schema);
        
        $query = JFactory::getDbo()->getQuery(true);
        $query
            ->select("COUNT(*)")
            ->from('#__tags');

        $this->assertEquals(24, JFactory::getDbo()->setQuery($query)->loadResult());
    }

    public function testOnJSpaceHarvestIngestWithWeblinks()
    {
        $harvest = JSpaceIngestionHarvest::getInstance();
        $harvest->bind($this->data);
        $harvest->originating_url = 'http://localhost/jspace/request_ore.php';
        $harvest->get('params')->set('harvest_type', 1);
        
        $dispatcher = JEventDispatcher::getInstance();

        JPluginHelper::importPlugin('jspace', 'oai', true, $dispatcher);
        
        $result = $dispatcher->trigger('onJSpaceHarvestDiscover', array($harvest->originating_url));

        $harvest->get('params')->loadArray($result[0]->toArray());

        $dispatcher->trigger('onJSpaceHarvestRetrieve', array($harvest));
        
        $dispatcher->trigger('onJSpaceHarvestIngest', array($harvest));
        
        $query = JFactory::getDbo()->getQuery(true);
        $query->select("COUNT(*)")->from('#__jspace_records')->where('alias <> \'root\'');
        
        $this->assertEquals(2, (int)JFactory::getDbo()->setQuery($query)->loadResult());
        
        $query = JFactory::getDbo()->getQuery(true);
        $query->select("COUNT(*)")->from('#__weblinks AS a')->join('inner', '#__jspace_references AS b ON a.id = b.id');

        $this->assertEquals(2, (int)JFactory::getDbo()->setQuery($query)->loadResult());
    }
    
    public function testOnJSpaceHarvestIngestWithAssets()
    {
        $harvest = JSpaceIngestionHarvest::getInstance();
        $harvest->bind($this->data);
        $harvest->originating_url = 'http://localhost/jspace/request_ore.php';
        $harvest->get('params')->set('harvest_type', 2);
        $harvest->get('params')->set('set', 'com_10049_26');
        
        $dispatcher = JEventDispatcher::getInstance();

        JPluginHelper::importPlugin('jspace', 'oai', true, $dispatcher);
        
        $result = $dispatcher->trigger('onJSpaceHarvestDiscover', array($harvest->originating_url));

        $harvest->get('params')->loadArray($result[0]->toArray());

        $dispatcher->trigger('onJSpaceHarvestRetrieve', array($harvest));
        
        $dispatcher->trigger('onJSpaceHarvestIngest', array($harvest));
        
        $query = JFactory::getDbo()->getQuery(true);
        $query->select("COUNT(*)")->from('#__jspace_records')->where('alias <> \'root\'');
        
        $this->assertEquals(2, (int)JFactory::getDbo()->setQuery($query)->loadResult());
        
        $query = JFactory::getDbo()->getQuery(true);
        $query->select("COUNT(*)")->from('#__jspace_assets');
        
        $this->assertEquals(2, (int)JFactory::getDbo()->setQuery($query)->loadResult());
    }
    
    public function testOnJSpaceHarvestWithDuplicateCacheData()
    {
        $harvest = JSpaceIngestionHarvest::getInstance();
        $harvest->bind($this->data);
        $harvest->get('params')->set('harvest_type', 0);
        $harvest->get('params')->set('set', 'com_10049_24');
        
        $dispatcher = JEventDispatcher::getInstance();

        JPluginHelper::importPlugin('jspace', 'oai', true, $dispatcher);
        
        $result = $dispatcher->trigger('onJSpaceHarvestDiscover', array($harvest->originating_url));

        $harvest->get('params')->loadArray($result[0]->toArray());

        $dispatcher->trigger('onJSpaceHarvestRetrieve', array($harvest));
        
        $dispatcher->trigger('onJSpaceHarvestIngest', array($harvest));

        $query = JFactory::getDbo()->getQuery(true);
        $query->select("COUNT(*)")->from('#__jspace_records')->where('alias <> \'root\'');
        
        $this->assertEquals(33, (int)JFactory::getDbo()->setQuery($query)->loadResult());
        
        $harvest->get('params')->set('discovery.url', 'http://localhost/jspace/request_updated.php');
        
        // harvest again. We should get shouldn't get duplicates.
        $dispatcher->trigger('onJSpaceHarvestRetrieve', array($harvest));
        
        $dispatcher->trigger('onJSpaceHarvestIngest', array($harvest));
        
        $query = JFactory::getDbo()->getQuery(true);
        $query->select("COUNT(*)")->from('#__jspace_records')->where('alias <> \'root\'');
        
        $this->assertEquals(34, (int)JFactory::getDbo()->setQuery($query)->loadResult());
    }
    
    public function testDuplicateAliases()
    {
        $harvest = JSpaceIngestionHarvest::getInstance();
        $harvest->bind($this->data);
        $harvest->get('params')->set('harvest_type', 1);
        $harvest->get('params')->set('set', 'col_123456789_20');
        
        $dispatcher = JEventDispatcher::getInstance();

        JPluginHelper::importPlugin('jspace', 'oai', true, $dispatcher);
        
        $result = $dispatcher->trigger('onJSpaceHarvestDiscover', array($harvest->originating_url));

        $harvest->get('params')->loadArray($result[0]->toArray());

        $dispatcher->trigger('onJSpaceHarvestRetrieve', array($harvest));
        
        $dispatcher->trigger('onJSpaceHarvestIngest', array($harvest));
        
        $query = JFactory::getDbo()->getQuery(true);
        $query->select("COUNT(*)")->from('#__jspace_records')->where('alias <> \'root\'');
        
        $this->assertEquals(33, (int)JFactory::getDbo()->setQuery($query)->loadResult());
        
        $query = JFactory::getDbo()->getQuery(true);
        $query
            ->select("id")
            ->from('#__jspace_records')
            ->order('id DESC');
       
        $record = JSpaceRecord::getInstance((int)JFactory::getDbo()->setQuery($query, 0, 1)->loadResult());
        
        $query = JFactory::getDbo()->getQuery(true);
        $query
            ->select("COUNT(*)")
            ->from('#__tags');

        $this->assertEquals(24, JFactory::getDbo()->setQuery($query)->loadResult());
       
        $harvest->harvested = null;
       
        $dispatcher->trigger('onJSpaceHarvestRetrieve', array($harvest));
        
        $dispatcher->trigger('onJSpaceHarvestIngest', array($harvest));
        
        $query = JFactory::getDbo()->getQuery(true);
        $query->select("COUNT(*)")->from('#__jspace_records')->where('alias <> \'root\'');
        
        $this->assertEquals(33, (int)JFactory::getDbo()->setQuery($query)->loadResult());
        
        $query = JFactory::getDbo()->getQuery(true);
        $query
            ->select("id")
            ->from('#__jspace_records')
            ->order('id DESC');
            
        $record = JSpaceRecord::getInstance((int)JFactory::getDbo()->setQuery($query, 0, 1)->loadResult());
        
        $query = JFactory::getDbo()->getQuery(true);
        $query
            ->select("COUNT(*)")
            ->from('#__tags');

        $this->assertEquals(24, JFactory::getDbo()->setQuery($query)->loadResult());
    }
    
    protected function getDataSet()
    {
        $dataset = parent::getDataSet();
        $dataset->addTable('jos_extensions', JSPACEPATH_TESTS.'/stubs/database/jos_extensions_no_storage.csv');
        
        return $dataset;
    }
}