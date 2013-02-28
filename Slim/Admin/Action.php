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
 * The basic column object.
 *
 * @package     Slim-Admin
 * @since	0.1.0
 *
 */

class Action extends Base
{
	/**
	 * @var string
	 */
	public $name;

	/**
	 * @var string
	 */
	public $alias;

	/**
	 * @var string
	 */
	public $submit;

	/**
	 * @var string
	 */
	public $display;

	/**
	 * @var function
	 */
	public $callable;

	/**
	 * @var Table
	 */
	public $table;

	/**
	 * @var mix
	 */
	protected $columns;
	protected $columnList;

	/**
	 * Constructor
	 * @param mix $conn The db connection
	 */
	public function __construct( $name, $settings = array() )
	{
		$this->alias = ucfirst( $name );
		$this->columnList = array();
		parent::__construct( $name, $settings );
	}

	/**
	 * Columns
	 *
	 */
	public function columns()
	{
		$columns = $this->columns;
		if( $columns ) {
			$table = $this->table;
			if( $table ) {
				$this->columns = null;
				$columns = is_string($columns) ? 
					(preg_split("/\s*[,]\s*/", $columns ) ) : $columns;
				for ($i = 0; $i < count($columns); $i++) {
					$col = $columns[$i];
					if(!is_array($col)){
						$col = array($col);
					}
					$this->columnList[] = call_user_func_array(array($this, "column"), $col);
				}
			}
		}
		return $this->columnList;
	}

	/**
	 *
	 * Column from this table
	 *
	 * @return \Slim\Admin\Column
	 */
	public function column( $name, $settings = array() )
	{
		return call_user_func_array(array($this->table, "column"), func_get_args());
	}
}

?>
