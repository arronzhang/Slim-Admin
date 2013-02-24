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
	 * Constructor
	 * @param mix $conn The db connection
	 */
	public function __construct( $userSettings = array() )
	{
		$this->settings = array();
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
}

?>
