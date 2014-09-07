<?php
jimport('jspace.archive.record');

require_once(JSPACEPATH_TESTS.'/core/case/database.php');
require_once(JSPACEPATH_TESTS.'/core/mock/session.php');

require_once(JPATH_ROOT.'/administrator/components/com_jspace/tables/recordhistory.php');

class JSpaceRecordTest extends TestCaseDatabase
{    
    public function setUp()
    {
        parent::setUp();
        
        $user = new JUser(525);
        
        $mockSession = $this->getMock('JSession', array('_start', 'get'));
        
        $mockSession->expects($this->any())->method('get')->will(
            $this->returnValue($user)
        );
        
        JFactory::$session = $mockSession;
    }
    
    public function testVersioningRequired()
    {
        $registry = new JRegistry;
        $registry->set('title', array('Record Test Case'));
        $registry->set('author', array('Hayden Young'));
    
        $record = new JSpaceRecord();
        $record->catid = 9;
        $record->set('title', 'Test Record');
        $record->set('language', '*');
        $record->set('metadata', $registry);
        $record->set('created_by', JFactory::getUser()->id);

        $record->save();
        
        $record->set('title', 'Test Record version 2');

        $record->save();

        JFactory::getApplication()->input->set('type_alias', 'com_jspace.record');
        JFactory::getApplication()->input->set('item_id', $record->id);
        JFactory::getApplication()->input->set('type_id', '23');
        
        require_once(JPATH_ROOT.'administrator/components/com_contenthistory/models/history.php');

        $model = new ContenthistoryModelHistory();

        $this->assertEquals(2, count($model->getItems()));
    }
    
    public function testVersioningIgnored()
    {
        $registry = new JRegistry;
        $registry->set('title', array('Record Test Case'));
        $registry->set('author', array('Hayden Young'));
    
        $record = new JSpaceRecord();
        $record->catid = 9;
        $record->set('title', 'Test Record');
        $record->set('language', '*');
        $record->set('metadata', $registry);
        $record->set('created_by', 525);
        $record->set('published', 0);
        $record->set('schema', '[No Schema]');

        $record->save();
        
        $record->set('modified', JFactory::getDate()->toSql());

        $record->save();

        JFactory::getApplication()->input->set('type_alias', 'com_jspace.record');
        JFactory::getApplication()->input->set('item_id', $record->id);
        JFactory::getApplication()->input->set('type_id', '23');
        
        require_once(JPATH_ROOT.'administrator/components/com_contenthistory/models/history.php');

        $model = new ContenthistoryModelHistory();

        $this->assertEquals(1, count($model->getItems()));   
    }
    
    public function testGetTree()
    {
        $registry = new JRegistry;
        $registry->set('title', array('Level 1'));
        $registry->set('author', array('Hayden Young'));
    
        // Level 1
        $record = new JSpaceRecord();
        $record->catid = 9;
        $record->set('title', 'Level 1');
        $record->set('language', '*');
        $record->set('metadata', $registry);
        $record->set('created_by', JFactory::getUser()->id);

        $record->save();
        
        $id = $record->id;
        $parent_id = $id;
        
        // Level 2
        $record->set('title', 'Level 2a');
        $record->set('parent_id', $parent_id);
        $record->set('id', null);

        $record->save();
        
        $id = $record->id;
        
        // Level 3
        $record->set('title', 'Level 3');
        $record->set('parent_id', $id);
        $record->set('id', null);
        
        $record->save();
        
        // Level 2
        $record->set('title', 'Level 2b');
        $record->set('parent_id', $parent_id);
        $record->set('id', null);

        $record->save();
        
        $record = JSpaceRecord::getInstance($parent_id);

        $tree = JSpaceRecord::getTree($parent_id);
        
        // @todo Better testing required.
        $this->assertNotNull($tree);
    }
    
    public function testLazyLoading()
    {
        $registry = new JRegistry;
        $registry->set('title', array('Level 1'));
        $registry->set('author', array('Hayden Young'));
    
        // Level 1
        $record = new JSpaceRecord();
        $record->catid = 9;
        $record->set('title', 'Level 1');
        $record->set('language', '*');
        $record->set('metadata', $registry);
        $record->set('created_by', JFactory::getUser()->id);

        $record->save();
        
        $id = $record->id;
        $parent_id = $id;
        
        // Level 2
        $record->set('title', 'Level 2a');
        $record->set('parent_id', $parent_id);
        $record->set('id', null);

        $record->save();
        
        $id = $record->id;
        
        // Level 3
        $record->set('title', 'Level 3');
        $record->set('parent_id', $id);
        $record->set('id', null);
        
        $record->save();
        
        // Level 2
        $record->set('title', 'Level 2b');
        $record->set('parent_id', $parent_id);
        $record->set('id', null);

        $record->save();
        
        $record = JSpaceRecord::getInstance($parent_id);
        
        $children = $record->getChildren();
        
        $this->assertEquals(2, count($children));
        
        $children = current($children)->getChildren();
        
        $this->assertEquals(1, count($children));
        
        $this->assertEquals(1, count($record->getCategory()));
    }

    /**
     * @expectedException        Exception
     * @expectedExceptionMessage Direct access to root node not allowed
     */
    public function testGetJSpaceRoot()
    {
        var_dump(JSpaceRecord::getTree(1));
    }
    
    protected function getDataSet()
    {
        $dataset = parent::getDataSet();
        $dataset->addTable('jos_extensions', JSPACEPATH_TESTS.'/stubs/database/jos_extensions_no_storage.csv');
        
        return $dataset;
    }
}
