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
 * The basic action manager object.
 *
 * @package     Slim-Admin
 * @since	0.1.0
 *
 */


class ActionManager extends Base
{
	/**
	 * Constructor
	 *
	 * @param mix $name The table name
	 */
	public function __construct()
	{
		parent::__construct( "action", array() );
		$this->childClass = "\\Slim\\Admin\\Action";
	}

	/*
	 * Action
	 */
	public function action($table, $name, $settings, $callable){
		if( !is_callable($callable) ) {
			throw new \InvalidArgumentException('$callable must a function');
		}
		if(is_string($settings) ) {
			$settings = array( "alias" => $settings );
		}
		$settings["callable"] = $callable;
		$settings["table"] = $table;
		return $this->child( $name, $settings );
	}

	public function actions(){
		return $this->childrenList;
	}
}

?>
