<?php
use JSpace\Archive\Asset;

class JSpaceArchiveAssetTest extends \PHPUnit_Framework_TestCase
{
    public function testInitiate()
    {
        $asset = new Asset();
		$asset->set('title', 'Test Asset');
		$this->assertEquals($asset->get('title'), 'Test Asset');
    }
}
