<?php
use JSpace\Ingestion\Harvest;
use \JFactory;
use \JArrayHelper;
use Joomla\Registry\Registry;

require_once(JSPACEPATH_TESTS.'/core/case/database.php');
require_once(JSPACEPATH_TESTS.'/core/mock/session.php');

class JSpaceIngestionHarvestTest extends \TestCaseDatabase
{
    public function testSave()
    {
        $data = array(
            'originating_url'=>'http://apps.who.int/iris/simple-search?query=Ebola',
            'harvester'=>'0',
            'frequency'=>1,
            'params'=>json_encode(array()),
            'state'=>1,
            'created'=>JFactory::getDate()->toSql(),
            'created_by'=>525,
            'catid'=>1
        );

        $registry = new Registry;
        $registry->set('harvest_type', 0);
        $registry->set('default.access', 0);
        $registry->set('default.language', '*');
        $registry->set('default.state', 1);

        $harvest = Harvest::getInstance();
        $harvest->bind($data);
        $harvest->set('params', $registry);
        $this->assertTrue($harvest->save());

        $actual = Harvest::getInstance($harvest->id);

        $this->assertEquals(JArrayHelper::fromObject($harvest), JArrayHelper::fromObject($actual));
    }
}