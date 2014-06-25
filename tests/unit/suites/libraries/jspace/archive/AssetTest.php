<?php
jimport('jspace.archive.asset');

class JSpaceAssetTest extends PHPUnit_Framework_TestCase
{
    public function testInitiate()
    {
        $asset = new JSpaceAsset();
		$asset->set('title', 'Test Asset');
		$this->assertEquals($asset->get('title'), 'Test Asset');
    }
}
