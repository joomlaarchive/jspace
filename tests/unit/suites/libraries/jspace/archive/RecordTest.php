<?php
jimport('jspace.archive.record');

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\content\LargeFileContent;

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
    
    public function testSaveRecordWithAssets()
    {
        $files = array();
    
        $root = vfsStream::setup();
        $files[] = vfsStream::newFile('image.jpg')
            ->withContent(LargeFileContent::withKilobytes(100))
            ->at($root);
        $files[] = vfsStream::newFile('document.pdf')
            ->withContent(LargeFileContent::withKilobytes(50))
            ->at($root);
    
        $_FILES = array(
            'original'=>array(
                array(
                    'name' => $files[0]->getName(),
                    'type' => 'image/jpeg',
                    'size' => $files[0]->size(),
                    'tmp_name' => $files[0]->url(),
                    'error' => 0
                ),
                array(
                    'name' => $files[1]->getName(),
                    'type' => 'application/pdf',
                    'size' => $files[1]->size(),
                    'tmp_name' => $files[1]->url(),
                    'error' => 0
                )
            )
        );
        
        $registry = new JRegistry;
        $registry->set('title', array('Record Test Case'));
        $registry->set('author', array('Hayden Young'));
    
        $record = new JSpaceRecord();
        $record->catid = 9;
        $record->set('title', 'Test Record');
        $record->set('language', '*');
        $record->set('metadata', $registry);
        $record->set('created_by', JFactory::getUser()->id);
        
        $record->save($_FILES);
        
        $record = JSpaceRecord::getInstance($record->id);
        $assets = $record->getAssets();
        
        $this->assertEquals(2, count($assets));
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
        
        require_once(JPATH_ROOT.'/administrator/components/com_contenthistory/models/history.php');

        $model = new ContenthistoryModelHistory();

        $this->assertEquals(2, count($model->getItems()));
        
        // check for the csv metadata.
        foreach ($model->getItems() as $item)
        {
            $data = json_decode($item->version_data);
            
            $this->assertEquals('{"title":"Record Test Case","author":"Hayden Young"}', $data->metadatapairs);
        }
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

        // A resave will update modified/modified by.
        $record->save();

        require_once(JPATH_ROOT.'/administrator/components/com_contenthistory/models/history.php');
        JFactory::getApplication()->input->set('type_alias', 'com_jspace.record');
        JFactory::getApplication()->input->set('type_id', '23');
        JFactory::getApplication()->input->set('item_id', $record->id);
        
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
        JSpaceRecord::getTree(1);
    }
    
    protected function getDataSet()
    {
        $dataset = parent::getDataSet();
        $dataset->addTable('jos_extensions', JSPACEPATH_TESTS.'/stubs/database/jos_extensions_no_storage.csv');
        
        return $dataset;
    }
}
