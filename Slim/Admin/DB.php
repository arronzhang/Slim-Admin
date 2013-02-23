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
	//public function __construct( $conn = null )
	//{
	//}

	/**
	 * Configure table
	 *
	 * @param  string $name The name of the table
	 * @param  string|array $settings  If a string, the name of the setting to set or retrieve. Else an associated array of setting names and values
	 * @param  mixed        $value If name is a string, the value of the setting identified by $name
	 *
	 * @return \Slim\Admin\Table
	 */
	public function table( $name, $settings, $value = null )
	{
	}

	/**
	 * Get tables
	 *
	 * @return array tables
	 */
	public function tables()
	{
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
