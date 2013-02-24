<?php

class BaseTest extends PHPUnit_Framework_TestCase
{
	/**
	 * configable
	 */
	public function testConfig()
	{
		$base = new \Slim\Admin\Base( "user", array( "a" => "b" ) );
		$this->assertEquals( "b", $base->config("a") );

		$base->config( array( "a" => "c" ) );
		$this->assertEquals( "c", $base->config("a") );

		$base->config( "a", "d" );
		$this->assertEquals( "d", $base->config("a") );

		$base->config( "name", "user" );
		$this->assertEquals( "user", $base->config("name") );
		$this->assertEquals( "user", $base->name );

		$base->name = "acc";
		$this->assertEquals( "acc", $base->config("name") );
	}

	/**
	 * Permit
	 */
	public function testPermit()
	{
		$base = new \Slim\Admin\Base( "user", array( "create" => 0, "del" => "yes" ) );
		$base->permit("create", 0);
		$this->assertEquals( 0, $base->permit("create") );
		$this->assertEquals( 1, $base->permit("update") );
		$this->assertEquals( true, (bool)$base->permit("del") );

		$base->permit( "update", 0 );
		$this->assertEquals( 0, $base->permit("update") );

		//string...
		$base->permit( "update", "name, !pass, add" );
		$res = $base->permit("update");
		$this->assertEquals( 4, $res["name"] );
		$this->assertEquals( 2, $res["add"] );
		$this->assertEquals( 0, $res["pass"] );

		//children
		$child = $base->child("name");
		$this->assertInstanceOf( "\\Slim\\Admin\\Base", $child );
		$this->assertEquals( $child, $base->child("name") );

		$pass = new \Slim\Admin\Base( "pass" );
		$child2 = $base->child( $pass );
		$this->assertEquals( $pass, $child2 );
		$this->assertEquals( 0, $pass->permit("update") );
		$this->assertEquals( 4, $child->permit("update") );

		$base->permit( "update", "name, pass, add" );
		$this->assertEquals( 3, $pass->permit("update") );
	}
}

?>
