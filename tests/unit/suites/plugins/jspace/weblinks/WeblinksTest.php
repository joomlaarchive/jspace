<?php
use JSpace\Archive\Record;

require_once(JSPACEPATH_TESTS.'/core/case/database.php');

class WeblinksTest extends \TestCaseDatabase
{
    public function setUp()
    {
        parent::setUp();

        JFactory::getDbo()->setQuery('UPDATE sqlite_sequence SET seq = (SELECT MAX(id) FROM jos_jspace_records) WHERE name="jos_jspace_records"')->execute();

        JFactory::getDbo()->setQuery('UPDATE sqlite_sequence SET seq = (SELECT MAX(id) FROM jos_jspace_references) WHERE name="jos_jspace_references"')->execute();

        JFactory::getDbo()->setQuery('UPDATE sqlite_sequence SET seq = (SELECT MAX(id) FROM jos_weblinks) WHERE name="jos_weblinks"')->execute();
    }

    public function testCreateItem()
    {
        $metadata = array();
        $metadata['title'] = array('Test Title');
        $metadata['author'] = array('Hayden Young');

        $record = new Record();
        $record->catid = 9;
        $record->set('title', 'Test Record');
        $record->set('language', '*');
        $record->set('path', 'test-record');
        $record->set('metadata', $metadata);
        $record->set('access', 0);

        $record->weblinks = array();
        $record->weblinks['weblink'][] = array('title'=>'Test Weblink', 'url'=>'http://ww.example.com');
        $record->weblinks['weblink'][] = array('title'=>'Test Weblink 2', 'url'=>'http://ww.example2.com');

        $record->save();

        $this->assertEquals(2, count($record->getReferences()));
    }

    public function testUpdateItem()
    {
        $metadata = array();
        $metadata['title'] = array('Test Title');
        $metadata['author'] = array('Hayden Young');

        $record = new Record();
        $record->catid = 9;
        $record->set('title', 'Test Record');
        $record->set('language', '*');
        $record->set('path', 'test-record');
        $record->set('metadata', $metadata);
        $record->set('access', 0);

        $record->weblinks = array();
        $record->weblinks['weblink'][] = array('title'=>'Test Weblink', 'url'=>'http://ww.example.com');
        $record->weblinks['weblink'][] = array('title'=>'Test Weblink 2', 'url'=>'http://ww.example2.com');

        $record->save();

        $record->weblinks = array();
        $record->weblinks['weblink'][] = array('id'=>1, 'title'=>'Test Weblink', 'url'=>'http://ww.example.com');
        $record->weblinks['weblink'][] = array('id'=>2, 'title'=>'Test Weblink 2 Updated', 'url'=>'http://ww.example2.com');
        $record->weblinks['weblink'][] = array('title'=>'Test Weblink 3', 'url'=>'http://ww.example3.com');

        $record->save();

        $this->assertEquals(3, count($record->getReferences()));
    }

    public function testDeleteReference()
    {
        $metadata = array();
        $metadata['title'] = array('Test Title');
        $metadata['author'] = array('Hayden Young');

        $record = new Record();
        $record->catid = 9;
        $record->set('title', 'Test Record');
        $record->set('language', '*');
        $record->set('path', 'test-record');
        $record->set('metadata', $metadata);
        $record->set('access', 0);

        $record->weblinks = array();
        $record->weblinks['weblink'][] = array('title'=>'Test Weblink', 'url'=>'http://ww.example.com');
        $record->weblinks['weblink'][] = array('title'=>'Test Weblink 2', 'url'=>'http://ww.example2.com');

        $record->save();

        $record->weblinks = array();
        $record->weblinks['weblink'][] = array('id'=>1, 'title'=>'Test Weblink', 'url'=>'http://ww.example.com');
        $record->weblinks['weblink'][] = array('title'=>'Test Weblink 3', 'url'=>'http://ww.example3.com');

        $record->save();

        $this->assertEquals(2, count($record->getReferences()));
    }

    public function testDeleteItem()
    {
        $database = JFactory::getDbo();
        $query = $database->getQuery(true);

        $metadata = array();
        $metadata['title'] = array('Test Title');
        $metadata['author'] = array('Hayden Young');

        $record = new Record();
        $record->catid = 9;
        $record->set('title', 'Test Record');
        $record->set('language', '*');
        $record->set('path', 'test-record');
        $record->set('metadata', $metadata);
        $record->set('access', 0);

        $record->weblinks = array();
        $record->weblinks['weblink'][] = array('title'=>'Test Weblink', 'url'=>'http://ww.example.com');
        $record->weblinks['weblink'][] = array('title'=>'Test Weblink 2', 'url'=>'http://www.example2.com');

        $record->save();

        $record->delete();

        $this->assertEquals(0, count($record->getReferences()));
    }
}