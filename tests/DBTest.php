<?php

class DBTest extends PHPUnit_Framework_TestCase
{
	/**
	 * configable
	 */
	public function testConfig()
	{
		$base = new \Slim\Admin\DB( null, array( "a" => "b" ) );
		$this->assertEquals( "b", $base->config("a") );

		$base->config( array( "a" => "c" ) );
		$this->assertEquals( "c", $base->config("a") );

		$base->config( "a", "d" );
		$this->assertEquals( "d", $base->config("a") );
	}

	/**
	 * test child tables
	 */

	public function testColumn()
	{
		$db = new \Slim\Admin\DB();
		$table = $db->table("name");
		$this->assertEquals( "Name", $table->alias );
		$table2 = $db->table("name", "NAME");
		$this->assertEquals( "NAME", $table->alias );
		$this->assertEquals( "NAME", $table2->alias );
		$this->assertEquals( $table, $table2 );

		$table = $db->table("pass", array("new" => true));
		$this->assertTrue( $table->config("new") );

		$tables = $db->tables();
		$this->assertEquals( 2, count( $tables ) );
	}

	/**
	 * test load
	 */

	public function testLoad()
	{
		//$conn = new \Jasny\MySQL\DB("127.0.0.1", "root", "public", "mysql");
		$db = new \Slim\Admin\DB();
		$db->load( array("user") );
		$tables = $db->tables();
		$this->assertEquals( 1, count($tables) );
		$this->assertEquals( "user", $tables[0]->name );
	}

}

?>
