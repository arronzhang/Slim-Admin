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
 * Base class
 *
 * The basic configable class
 *
 * @package     Slim-Admin
 * @since	0.1.0
 *
 */

class Base
{
	/**
	 * @var string
	 */
	public $name;

	/**
	 * @var array
	 */
	protected $settings;

	/**
	 * @var array
	 */
	protected $permissions;

	/**
	 * @var array
	 */
	protected $children;
	/**
	 * @var array
	 */
	protected $childrenList;


	/**
	 * Constructor
	 * @param mix $conn The db connection
	 */
	public function __construct( $name = null, $userSettings = array() )
	{
		$this->name = $name;
		$this->settings = array();
		$this->children = array();
		$this->childrenList = array();
		$this->permissions = array();
		$this->config( $userSettings );
	}

	/**
	 * Configure
	 *
	 * @param  string|array $name  If a string, the name of the setting to set or retrieve. Else an associated array of setting names and values
	 * @param  mixed        $value If name is a string, the value of the setting identified by $name
	 * @return mixed        The value of a setting if only one argument is a string
	 *
	 */
	public function config( $name, $value = null )
	{
		if (func_num_args() === 1) {
			if (is_array($name)) {
				//$this->settings = array_merge($this->settings, $name);
				foreach ( $name as $key => $value ) {
					$this->settings[$key] = $value;
					if( property_exists( $this, $key ) ) {
						$this->$key = $value;
					}
				}
			} else {
				return property_exists( $this, $name ) ? $this->$name : ( isset($this->settings[$name]) ? $this->settings[$name] : null );
			}
		} else {
			$this->settings[$name] = $value;
			if( property_exists( $this, $name ) ) {
				$this->$name = $value;
			}
		}
	}

	/**
	 * Permission control
	 *
	 * @param  string 	$name of the permission
	 * @param  number|string|array        $value The permission level; It will define the children permission when $value is array or string.
	 * @return mixed        The permission level of this class, return `true` if not set permission by $name
	 *
	 */
	public function permit( $name, $value = null )
	{
		if (func_num_args() === 1) {
			return isset( $this->permissions[$name] ) ? $this->permissions[$name] : 1;
		} else {
			if( is_string( $value ) ) {
				$value = preg_split( "/\s*[,]\s*/", trim( $value ) );
			} else if( !is_array( $value ) ) {
				$this->permissions[$name] = $value;
				return;
			}
			//Children...
			$ar = array();
			$len = count($value);
			for ($i = 0; $i < $len; $i++) {
				$key = $value[$i];
				if( substr( $key, 0, 1) == "!" ) {
					$key = substr($key, 1);
					$ar[ $key ] = 0;
				} else {
					$ar[ $key ] = $len - $i + 1;
				}
				if( isset( $this->children[ $key ] ) ) {
					$this->children[ $key ]->permit( $name, $ar[ $key ] );
				}
			}

			$this->permissions[$name] = $ar;
		}
	}

	/**
	 * Permission control
	 *
	 * @param  string|Base 	$name of the child or a Base object
	 * @param  array 	$settings of the child
	 *
	 */
	public function child( $name, $settings = array() ) {
		$child = null;
		if( $name instanceof Base ) {
			$child = $name;
			$name = $child->name;
		}
		if( !is_string( $name ) ) {
			throw new \InvalidArgumentException('$name must a string or Base object.');
		}
		if( !isset( $this->children[ $name ] ) ) {
			if( !$child )
				$child = new Base( $name );
			$this->children[ $name ] = $child;
			$this->childrenList[] = $child;
			//auto complete permit
			foreach ($this->permissions as $key => $perm) {
				if( is_array( $perm ) && isset( $perm[$name] ) ) {
					$child->permit( $key, $perm[$name] );
				}
			}
		} else {
			$child = $this->children[ $name ];
		}

		$args = func_get_args();
		array_shift( $args );
		if( !empty( $args ) )
			call_user_func_array( array( $child, "config"), $args );

		return $child;
	}
}

?>
