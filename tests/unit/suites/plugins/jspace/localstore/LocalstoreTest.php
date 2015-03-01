<?php
use JSpace\Archive\Record;
use JSpace\Archive\AssetHelper;

\JLoader::import('joomla.filesystem.folder');

require_once(JSPACEPATH_TESTS.'/core/case/database.php');

// @todo query dspace for info to assert tests against.
class LocalstoreTest extends \TestCaseDatabase
{
    public $url = null;

    public $local = null;

    public function setUp()
    {
        parent::setUp();

        JFactory::getDbo()->setQuery('UPDATE sqlite_sequence SET seq = (SELECT MAX(id) FROM jos_jspace_records) WHERE name="jos_jspace_records"')->execute();

        $plugin = JPluginHelper::getPlugin('jspace', 'localstore');

        $params = new JRegistry();
        $params->loadString($plugin->params);

        $this->local = $params->get('path');

        JFolder::create($this->local);
    }

    public function tearDown()
    {
        JFolder::delete($this->local);
    }

    public function testCreateItem()
    {
        $files = $this->getFiles();

        $new = $this->createRecord();
        $new->save($files);

        $query = JFactory::getDbo()->getQuery(true);
        $query->select('*')->from('#__jspace_records')->where('id=2');

        $record = JFactory::getDbo()->setQuery($query)->loadAssoc();

        $this->assertEquals($record['title'], 'Test Title');
        $this->assertEquals($record['metadata'], '{"title":["Test Title"],"author":["Hayden Young"]}');

        $this->assertEquals(2, count($new->getAssets()));

        foreach ($new->getAssets() as $asset) {
            $this->assertTrue(\JSpace\Filesystem\File::exists(AssetHelper::buildStoragePath($new->id, $this->local).'/'.$asset->hash));
        }
    }

    public function testUpdateItem()
    {
        $files = $this->getFiles();

        // create a new record before trying to edit it.
        $new = $this->createRecord();
        $new->save($files);

        $metadata = array();
        $metadata['title'] = array('Test Title Updated');
        $metadata['author'] = array('Hayden Young');
        $metadata['description'] = array('Update description.');

        $files = $this->getFiles2();

        $record = new Record();
        $record->id = $new->id;
        $record->catid = 9;
        $record->set('title', 'Updated Title');
        $record->set('language', '*');
        $record->set('path', 'updated-path');
        $record->set('metadata', $metadata);

        $result = (bool)$record->save($files);

        $query = JFactory::getDbo()->getQuery(true);
        $query->select('*')->from('#__jspace_records')->where('id=2');

        $record = JFactory::getDbo()->setQuery($query)->loadAssoc();

        $this->assertEquals($record['title'], 'Updated Title');
        $this->assertEquals($record['metadata'], '{"title":["Test Title Updated"],"author":["Hayden Young"],"description":["Update description."]}');

        $this->assertTrue(JFolder::exists($this->local.'/050'));
        $this->assertEquals(3, count($new->getAssets()));

        foreach ($new->getAssets() as $asset) {
            $this->assertTrue(\JSpace\Filesystem\File::exists(AssetHelper::buildStoragePath($new->id, $this->local).'/'.$asset->hash));
        }
    }

    public function testDeleteItem()
    {
        $files = $this->getFiles();

        $record = $this->createRecord();
        $result = $record->save($files);
        $this->assertTrue($result);

        $this->assertTrue(JFolder::exists($this->local.'/050'));

        $record->delete();

        $this->assertEquals(0, count($record->getAssets()));

        $this->assertFalse(JFolder::exists($this->local.'/050'));
    }

    private function createRecord()
    {
        $metadata = array();
        $metadata['title'] = array('Test Title');
        $metadata['author'] = array('Hayden Young');

        $record = new Record();
        $record->catid = 9;
        $record->set('title', 'Test Title');
        $record->set('language', '*');
        $record->set('path', 'test-record');
        $record->set('metadata', $metadata);

        return $record;
    }

    private function getFiles()
    {
        $stubs = dirname(dirname(dirname(dirname(dirname(__FILE__))))).'/stubs/files';

        $files = array();
        $files["original"] = array();

        $files["original"][] = array(
            'name'=>'file1.jpg',
            'type'=>'image/jpg',
            'tmp_name'=>$stubs.'/file1.jpg',
            'error'=>0,
            'size'=>515621);

        $files["original"][] = array(
            'name'=>'file2.jpg',
            'type'=>'image/jpg',
            'tmp_name'=>$stubs.'/file2.jpg',
            'error'=>0,
            'size'=>248198);

        return $files;
    }

    private function getFiles2()
    {
        $stubs = dirname(dirname(dirname(dirname(dirname(__FILE__))))).'/stubs/files';

        $files = array();

        $files["original"][] = array(
            'name'=>'file3.jpg',
            'type'=>'image/jpg',
            'tmp_name'=>$stubs.'/file3.jpg',
            'error'=>0,
            'size'=>84713);

        return $files;
    }
}