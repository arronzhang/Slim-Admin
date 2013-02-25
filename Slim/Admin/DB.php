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
	 * @var number
	 */
	public static $pageSize = 15;

	/**
	 * @var mix
	 */
	protected $conn;

	/**
	 * @var array
	 */
	protected $tables;

	/**
	 * Constructor
	 * @param mix $conn The db connection
	 */
	public function __construct( $conn = null, $settings = array() )
	{
		parent::__construct( null, $settings );
	}

	/**
	 * Configure table
	 *
	 * @param  string $name The name of the column
	 * @param  string|array $settings  If a string, the name of the setting to set or retrieve. Else an associated array of setting names and values
	 *
	 * @return \Slim\Admin\Table
	 */
	public function table( $name, $settings = array() )
	{
		if( func_num_args() < 3 && is_string($settings) ) {
			$settings = array( "alias" => $settings );
		}
		if( !isset( $this->children[ $name ] ) ) {
			$name = new Table( $name );
		}
		return $this->child( $name, $settings );
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
	 * @return array tables
	 */
	public function load()
	{
	}
}

?>
