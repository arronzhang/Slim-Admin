<?php

class BaseTest extends PHPUnit_Framework_TestCase
{
	/**
	 * configable
	 */
	public function testConfig()
	{
		$base = new \Slim\Admin\Base( array( "a" => "b" ) );
		$this->assertEquals( "b", $base->config("a") );

		$base->config( array( "a" => "c" ) );
		$this->assertEquals( "c", $base->config("a") );

		$base->config( "a", "d" );
		$this->assertEquals( "d", $base->config("a") );
	}
}

?>
