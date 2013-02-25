<?php

class ColumnTest extends PHPUnit_Framework_TestCase
{
	/**
	 * configable
	 */
	public function testConfig()
	{
		$base = new \Slim\Admin\Column( "name", array( "a" => "b" ) );
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
		$column = new \Slim\Admin\Column( "name", array( "a" => "b" ) );
		$column->name = "user";
		$this->assertEquals( "user", $column->name );
		$this->assertEquals( "Name", $column->label );
	}
}

?>
