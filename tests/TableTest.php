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

	/**
	 * test child columns
	 */

	public function testColumn()
	{
		$table = new \Slim\Admin\Table("users", array( "a" => "b" ) );
		$column = $table->column("name");
		$this->assertEquals( "Name", $column->label );
		$column2 = $table->column("name", "NAME");
		$this->assertEquals( "NAME", $column->label );
		$this->assertEquals( "NAME", $column2->label );
		$this->assertEquals( $column, $column2 );

		$column = $table->column("pass", array("new" => true));
		$this->assertTrue( $column->config("new") );

		$columns = $table->columns();
		$this->assertEquals( 2, count( $columns ) );
	}

}

?>
