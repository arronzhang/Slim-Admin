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

class Pager
{
	/**
	 * @var number
	 */
	public static $display = 10;

	/**
	 * @var number
	 */
	public $page;

	/**
	 * @var number
	 */
	public $per_size;

	/**
	 * @var number
	 */
	public $offset;

	/**
	 * @var number
	 */
	public $total;

	/**
	 * @var number
	 */
	public $prev;

	/**
	 * @var number
	 */
	public $next;

	/**
	 * @var number
	 */
	public $pages;

	/**
	 * @var number
	 */
	public $len;

	/**
	 * Constructor
	 * @param mix $conn The db connection
	 */
	public function __construct( $page = null, $num = 0, $per_size = 15 )
	{
		$this->config( $page, $num, $per_size );
	}

	/**
	 * Calculate page
	 *
	 */
	public function config( $page, $num, $per_size ) {
		$page = (int)$page;
		if( $page < 1 )
			$page = 1;

		$offset = ($page - 1) * $per_size;

		$len = ceil( $num / $per_size );
		$display = self::$display;
		$start = $page - (int)($display / 2);
		if( $start < 1 )
			$start = 1;
		$end = $start + $display - 1;
		if( $end > $len ) {
			$end = $len;
			$start = $end - $display + 1;
			$start = $start < 1 ? 1 : $start;
		}
		$pages = array();
		for ($i = $start; $i <= $end; $i++) {
			$pages[] = $i;
		}
		$prev = $page == 1 ? null : $page - 1;
		$next = $page == $len ? null : $page + 1;

		$this->per_size = $per_size;
		$this->total = $num;
		$this->offset = $offset;
		$this->page = $page;
		$this->len = $len;
		$this->pages = $pages;
		$this->prev = $prev;
		$this->next = $next;
	}
}

?>
