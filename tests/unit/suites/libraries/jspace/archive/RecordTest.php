<?php
jimport('jspace.archive.record');

require_once(JSPACEPATH_TESTS.'/core/case/database.php');
require_once(JSPACEPATH_TESTS.'/core/mock/session.php');

class JSpaceRecordTest extends TestCaseDatabase
{
    public function testVersioningRequired()
    {
        $user = new JUser(525);
        
        $mockSession = $this->getMock('JSession', array('_start', 'get'));
        
        $mockSession->expects($this->any())->method('get')->will(
            $this->returnValue($user)
        );
        
        JFactory::$session = $mockSession;

        $registry = new JRegistry;
        $registry->set('title', array('Record Test Case'));
        $registry->set('author', array('Hayden Young'));
    
        $record = new JSpaceRecord();
        $record->catid = 9;
        $record->set('title', 'Test Record');
        $record->set('language', '*');
        $record->set('path', 'test-record');
        $record->set('metadata', $registry);
        $record->set('created_by', 525);

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
        $user = new JUser(525);
        
        $mockSession = $this->getMock('JSession', array('_start', 'get'));
        
        $mockSession->expects($this->any())->method('get')->will(
            $this->returnValue($user)
        );
        
        JFactory::$session = $mockSession;

        $registry = new JRegistry;
        $registry->set('title', array('Record Test Case'));
        $registry->set('author', array('Hayden Young'));
    
        $record = new JSpaceRecord();
        $record->catid = 9;
        $record->set('title', 'Test Record');
        $record->set('language', '*');
        $record->set('path', 'test-record');
        $record->set('metadata', $registry);
        $record->set('created_by', 525);
        $record->set('published', 0);
        $record->set('hits', 0);
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
    
    protected function getDataSet()
    {
        $dataset = parent::getDataSet();
        $dataset->addTable('jos_extensions', JSPACEPATH_TESTS.'/stubs/database/jos_extensions_no_storage.csv');
        
        return $dataset;
    }
}
