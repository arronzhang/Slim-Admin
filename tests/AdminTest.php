<?php

$_SERVER["REQUEST_METHOD"] = "GET";
$_SERVER["REMOTE_ADDR"] = "192.168.1.1";
$_SERVER["REQUEST_URI"] = "/";
$_SERVER["SERVER_NAME"] = "192.168.1.1";
$_SERVER["SERVER_PORT"] = "80";

class AdminTest extends PHPUnit_Framework_TestCase
{
	/**
	 * configable
	 */
	public function testConfig()
	{

		$app = new \Slim\Admin();
		$this->assertInstanceOf( "\\Slim\\Admin\\DB", $app->db() );
		//$this->assertEquals( "b", $base->config("a") );

		//$base->config( array( "a" => "c" ) );
		//$this->assertEquals( "c", $base->config("a") );

		//$base->config( "a", "d" );
		//$this->assertEquals( "d", $base->config("a") );
	}

}

?>
