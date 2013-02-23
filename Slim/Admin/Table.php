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
 * Table
 *
 * The basic table object.
 *
 * @package     Slim-Admin
 * @since	0.1.0
 *
 */

class Table extends Base
{
	/**
	 * @var number
	 */
	public $pageSize = 15;

	/**
	 * @var string
	 */
	public $name;

	/**
	 * @var string
	 */
	public $alias;

	/**
	 * @var mix
	 */
	protected $conn;

	/**
	 * @var array
	 */
	protected $columns;

	/**
	 * Configure column
	 *
	 * @param  string $name The name of the column
	 * @param  string|array $settings  If a string, the name of the setting to set or retrieve. Else an associated array of setting names and values
	 * @param  mixed        $value If name is a string, the value of the setting identified by $name
	 *
	 * @return \Slim\Admin\Table
	 */
	public function column( $name, $settings, $value = null )
	{
	}


	/**
	 * Get columns
	 *
	 * @return array
	 */
	public function columns()
	{
	}

	/**
	 * Association
	 *
	 */
	public function has( $table, $remoteKey, $locDisplayKey )
	{
	}

	public function belong( $table, $locKey, $remoteDisplayKey )
	{
	}

	/**
	 * Format data
	 *
	 */
	public function format( $data )
	{
	}

	/**
	 * Load columns config from database.
	 *
	 */

	public function load() 
	{
	}

	public function key()
	{
	}

	public function all( $page, $sort = null, $filters = array(), $ignoreAssociation = false )
	{
	}

	public function pager( $page, $filters = array() )
	{
	}

	public function find( $id, $ignoreAssociation = false )
	{
	}

	public function create( $values, $ignorePermission = false )
	{
	}

	public function update( $id, $values, $ignorePermission = false )
	{
	}

	public function delete( $id, $ignorePermission = false )
	{
	}
}

?>
