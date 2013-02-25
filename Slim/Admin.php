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

namespace Slim;

/**
 * Admin
 *
 * @since 0.1.0
 *
 */

class Admin extends \Slim\Slim
{

	/**
	 * @var db
	 */
	protected $db;

	/**
	 * @var tables
	 */
	protected $tables;

	/**
	 * @var table
	 */
	protected $table;

	/**
	 * Constructor
	 * @param  array $settings Associative array of application settings
	 */
	public function __construct( $settings = array() ) {
		if( !isset( $settings["view"] ) ) {
			$settings["view"] = new \Slim\Extras\Views\Twig();
		}
		parent::__construct( $settings );
		$this->db = new Admin\DB( $this->config("conn") );
	}

	/**
	 * db
	 * @return  \Slim\Admin\DB
	 */
	public function db(){
		return $this->db;
	}

	/**
	 * table list
	 * @return  array
	 */
	public function tables(){
		return $this->tables;
	}

	/**
	 * current table
	 * @return  \Slim\Admin\Table
	 */
	public function table( $table = null ){
		if( func_num_args() ) {
			$this->table = $table;
		}
		return $this->table;
	}

	/**
	 * admin
	 * like run
	 * @return  \Slim\Admin\DB
	 */
	public function admin(){
		$this->db->load();
		$tables = $this->db->tables();
		usort( $tables, function($a, $b) {
			return $b->permit("manage") - $a->permit("manage");
		} );
		$this->tables = $tables;

		$view = $this->view();
		$view->setData( "tables", $tables );

		//Set default router...
		$len = count($tables);
		for ($i = 0; $i < $len; $i++) {
			$table = $tables[$i];
			if( $table->permit("manage") ) {
				$this->index( $table );
				$this->create( $table );
			}
		}

		for ($i = 0; $i < $len; $i++) {
			$table = $tables[$i];
			if( $table->permit("manage") ) {
				$table->url = $this->urlFor( $table->name );
			}
		}
		$this->run();
	}

	/**
	 * Router for index data
	 */
	public function index($table, $callable = null){
		$table = $this->db->table( $table );
		$name = $table->name;
		$app = $this;
		$this->get("/" . $name, function() use ($table, $app, $callable) {
			$table->load();
			$app->table( $table );
			$filter = $app->request()->get();
			$app->view()->appendData( array(
				"table" => $table,
			) );
			if( is_callable( $callable ) ) {
				call_user_func( $callable );
			} else {
				$app->render("list.html.twig");
			}
		})->name( $name );
	}

	/**
	 * Router for create data
	 */
	public function create($table, $callable = null){
		$table = $this->db->table( $table );
		$name = $table->name;
		$app = $this;
		$this->get("/" . $name . "/new", function() use ($table, $app, $callable) {
			$table->load();
			$app->table( $table );
			$app->view()->appendData( array(
				"table" => $table,
				"data" => $app->request()->get(),
			) );
			if( is_callable( $callable ) ) {
				call_user_func( $callable );
			} else {
				$app->render("new.html.twig");
			}
		})->name( $name . "_new" );
	}

	/**
	 * Router for save data
	 */
	public function save($table, $callable = null){
		$table = $this->db->table( $table );
		$name = $table->name;
		$app = $this;
		$this->post("/" . $name . "/new", function() use ($table, $app, $callable) {
			$table->load();
			$app->table( $table );
			$data = $app->request()->post();
			$app->view()->appendData( array(
				"table" => $table,
			) );
			if( is_callable( $callable ) ) {
				call_user_func( $callable );
			} else {
				$app->render("new.html.twig");
			}
		});
	}

	/**
	 * Get the URL for a named route
	 * @param  string               $name       The route name
	 * @param  array                $params     Associative array of URL parameters and replacement values
	 * @param  array                $qs     Query string for the url
	 * @throws \RuntimeException    If named route does not exist
	 * @return string
	 */
	public function urlFor($name, $params = array(), $qs = array() )
	{
		if( !is_array($params) ) {
			$params = array( "id" => $params );
		}
		$path = parent::urlFor( $name, $params );
		return $path;
		if( func_num_args() > 2 ) {
			$qs = call_user_func_array("array_merge", array_slice( func_get_args(), 2 ));
			$tmp = array();
			foreach ($qs as $key => $val) {
				if( $val !== "" ) {
					if( is_array( $val ) ) {
						foreach ($val as $v) {
							$tmp[] = $key . "[]=" . $v;
						}
					} else {
						$tmp[] = $key . "=" . $val;
					}
				}
			}
			$qs = implode( $tmp, "&" );
			return empty( $qs ) ? $path : ( $path . "?" . $qs );
		}
		return $path;
	}

	/********************************************************************************
	 * PSR-0 Autoloader
	 *
	 * Do not use if you are using Composer to autoload dependencies.
	 *******************************************************************************/

	/**
	 * Slim PSR-0 autoloader
	 */
	public static function autoload($className)
	{
		$thisClass = str_replace(__NAMESPACE__.'\\', '', __CLASS__);

		$baseDir = __DIR__;

		if (substr($baseDir, -strlen(__NAMESPACE__)) === __NAMESPACE__) {
			$baseDir = substr($baseDir, 0, -strlen(__NAMESPACE__));
		}


		$className = ltrim($className, '\\');
		$fileName  = $baseDir;
		$namespace = '';
		if ($lastNsPos = strripos($className, '\\')) {
			$namespace = substr($className, 0, $lastNsPos);
			$className = substr($className, $lastNsPos + 1);
			$fileName  .= str_replace('\\', DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
		}
		$fileName .= str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';

		if (file_exists($fileName)) {
			require $fileName;
		}
	}

	/**
	 * Register PSR-0 autoloader
	 */
	public static function registerAutoloader()
	{
		spl_autoload_register(__NAMESPACE__ . "\\Admin::autoload");
	}

}

?>
