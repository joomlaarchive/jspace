<?php
jimport('jspace.archive.record');

class JSpaceRecordTest extends PHPUnit_Framework_TestCase
{
    public function testInitiate()
    {
        $record = new JSpaceRecord();
		$record->set('title', 'Test Record');
		$this->assertEquals($record->get('title'), 'Test Record');
    }
}
