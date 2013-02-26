<?php

/**
 * Slim-Admin
 *
 * @author      Arron <arronzhang@me.com>
 * @copyright   2013 Arron
 * @version     0.1.0
 * @package     Slim-Admin
 *
 * MIT LICENSE
 */

namespace Slim\Admin;

/**
 * DB
 *
 * The basic database object.
 *
 * @package     Slim-Admin
 * @since	0.1.0
 *
 */

class DB extends Base
{
	/**
	 * @var mix
	 */
	protected $conn;

	/**
	 * @var mix
	 */
	protected $cache;

	/**
	 * Constructor
	 * @param mix $conn The db connection
	 */
	public function __construct( $conn = null, $settings = array() )
	{
		$this->conn = $conn;
		parent::__construct( "db", $settings );
		$this->childClass = "\\Slim\\Admin\\Table";
	}

	/**
	 * Configure connection
	 *
	 */
	public function conn( $conn = null ) {
		if( func_num_args() ) {
			$this->conn = $conn;
		}
		return $this->conn;
	}

	/**
	 * Configure table
	 *
	 * @param  string $name The name of the table
	 * @param  string|array $settings  If a string, the name of the setting to set or retrieve. Else an associated array of setting names and values
	 *
	 * @return \Slim\Admin\Table
	 */
	public function table( $name, $settings = array() )
	{
		if( func_num_args() < 3 && is_string($settings) ) {
			$settings = array( "alias" => $settings );
		}
		$child = $this->child( $name, $settings );
		if( !$child->config("db") ) {
			$child->config("db", $this);
		}
		return $child;
	}

	/**
	 * Get tables
	 *
	 * @return array tables
	 */
	public function tables()
	{
		return $this->childrenList;
	}

	/**
	 * Load tables config from database
	 * 
	 * @param  array $data Not fetch data from $conn If data give.
	 *
	 * @return array loaded data..
	 *
	 */
	public function load( $data = array() )
	{
		if( func_num_args() ) {
			if( is_array( $data ) ) {
				for ($i = 0; $i < count($data); $i++) {
					$this->table($data[$i]);
				}
			}
			return $data;
		} else {
			if( $this->conn ) {
				if ( !$this->cache ) 
					$this->cache = $this->conn->fetchColumn("SHOW TABLES", MYSQLI_NUM);
				return $this->load( $this->cache );
			} else {
				//throw not conn
			}
		}
	}
}

?>
