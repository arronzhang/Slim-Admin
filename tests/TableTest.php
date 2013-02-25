<?php

class TableTest extends PHPUnit_Framework_TestCase
{
	/**
	 * configable
	 */
	public function testConfig()
	{
		$base = new \Slim\Admin\Table( "users", array( "a" => "b" ) );
		$this->assertEquals( "b", $base->config("a") );

		$base->config( array( "a" => "c" ) );
		$this->assertEquals( "c", $base->config("a") );

		$base->config( "a", "d" );
		$this->assertEquals( "d", $base->config("a") );
	}

	/**
	 * test attributes
	 */

	public function testName()
	{
		$table = new \Slim\Admin\Table("users", array( "a" => "b" ) );
		$table->name = "user";
		$this->assertEquals( "user", $table->name );
		$this->assertEquals( "Users", $table->alias );
	}
}

?>
