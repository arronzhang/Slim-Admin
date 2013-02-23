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

class Column extends Base
{
	/**
	 * @var string
	 */
	public $name;

	/**
	 * @var string
	 */
	public $label;

	/**
	 * @var string
	 */
	public $type;

	/**
	 * Extra data
	 *
	 * @var mix
	 */
	public $extra;

	/**
	 * Column help info
	 *
	 * @var string
	 */
	public $help;

	/**
	 * Column error message
	 *
	 * @var string
	 */
	public $error;

	/**
	 * Data format method
	 *
	 * @var string
	 */
	public $formatter;

	/**
	 * Format data
	 *
	 */
	public function format( $data )
	{
	}
}

?>
