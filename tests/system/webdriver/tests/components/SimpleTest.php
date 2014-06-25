<?php
require_once 'bootstrap.php';

use SeleniumClient\WebDriver;
use SeleniumClient\By;

class SimpleTest extends PHPUnit_Framework_TestCase
{
	public function testInitiate()
    {
		$webDriver = new WebDriver();
		$webDriver->get("http://localhost/joomla33/administrator");
		
		$webDriver->findElement(By::name("username"))->sendKeys("haydenyoung");
		$webDriver->findElement(By::name("passwd"))->sendKeys("\$land0c%");
		$webDriver->findElement(By::cssSelector("button[class='btn btn-primary btn-large']"))->click();
    }
}