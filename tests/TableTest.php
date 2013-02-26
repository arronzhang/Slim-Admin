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

	/**
	 * test urlFor
	 */

	public function testUrlFor()
	{
		$table = new \Slim\Admin\Table("users", array( "url" => "/users" ) );
		$this->assertEquals( "/users", $table->url );
		$this->assertEquals( "/users/new", $table->urlFor("new") );
		$this->assertEquals( "/users/new?a=1", $table->urlFor("new", array("a" => "1")) );
		$this->assertEquals( "/users/new?a=1&b=2", $table->urlFor("new", array("a" => "1", "b" => "2")) );
		$this->assertEquals( "/users/new?a=1&b=2", $table->urlFor("new", array("a" => "1"), array( "b" => "2")) );
		$this->assertEquals( "/users?a=1&b=2", $table->urlFor(array("a" => "1"), array( "b" => "2")) );
		$this->assertEquals( "/users?a=1", $table->urlFor(array("a" => "1")) );
		$this->assertEquals( "/users?a=1&b=2&c=3", $table->urlFor(array("a" => "1"), array( "b" => "2"), array("c"=>"3")) );
		$this->assertEquals( "/users/new/e?a=1&b=2&c=3", $table->urlFor("new", "e", array("a" => "1"), array( "b" => "2"), array("c"=>"3")) );
	}
}

?>
