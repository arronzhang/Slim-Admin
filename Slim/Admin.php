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
	 * @var DB
	 */
	protected $db;

	/**
	 * @var array
	 */
	protected $tables;

	/**
	 * @var Table
	 */
	protected $table;

	/**
	 * @var mix
	 */
	protected $data;

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
	public function tables( $tables = null  ){
		if( func_num_args() ) {
			$this->tables = $tables;
			$this->view()->setData( "tables", $tables );
		}
		return $this->tables;
	}

	/**
	 * current table
	 * @return  \Slim\Admin\Table
	 */
	public function table( $table = null ){
		if( func_num_args() ) {
			$this->table = $table;
			$this->view()->setData( "table", $table );
		}
		return $this->table;
	}

	/**
	 * current table
	 * @return  \Slim\Admin\Table
	 */
	public function data( $data = null ){
		if( func_num_args() ) {
			$this->data = $data;
			$this->view()->setData( "data", $data );
		}
		return $this->data;
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
		$this->tables( $tables );

		//Set default router...
		$len = count($tables);
		for ($i = 0; $i < $len; $i++) {
			$table = $tables[$i];
			if( $table->permit("manage") ) {
				$this->index( $table );
				if( $table->permit("create") ) {
					$this->create( $table );
					$this->save( $table );
				}
			}
		}

		for ($i = 0; $i < $len; $i++) {
			$table = $tables[$i];
			if( $table->permit("manage") ) {
				$table->url = $this->urlFor( $table->name );
			}
		}

		$this->hookColumn();
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
			$req = $app->request();

			$app->data( 
				$table->conditions( $req->get() )->sort( 
					$req->get("sort") 
				)->pager( $req->get("page") )->all() 
			);

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
			$app->data( (object)$app->request()->get() );
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
			$data = (object)$app->request()->post();
			$app->data( $data );
			try{
				$app->applyHookColumn( $data );
				if( is_callable( $callable ) ) {
					call_user_func( $callable );
				} else {
					if( $table->create( $data ) ) {
						$app->flash("success", "新增成功!");
						if( $app->request()->post("_next") ) {
							$app->redirect( $table->url . "/new" );
						} else {
							$app->redirect( $table->url );
						}

					} else {
						$app->flashNow( "error", "新增失败" );
						$app->render("new.html.twig");
					}
				}
			} catch( Exception $e ) {
				$app->flashNow( "error", $e->getMessage() );
				$app->render("new.html.twig");
			}
		});
	}

	public function hookColumn() {
		$app = $this;
		$this->hook("admin.column.image", function ( $option ) use ($app) {
			$column = $option[0];
			$data = $option[1];
			$storage = new \Upload\Storage\FileSystem( $app->config("image.path") );
			$name = $column->name;
			if( isset($_FILES[$name]) ){
				$file = new \Upload\File( $name, $storage );
				if( $file->isOk() ) {
					//$file->setName(time() . rand( 1 , 10000 ));
					$file->addValidations(array(
						new \Upload\Validation\Mimetype(
							array( 'image/png', 'image/jpeg', 'image/jpg', 'image/gif', 'image/bmp' )
						),
					));
					$file->upload();
					$data->$name = $file->getNameWithExtension();
				}
			}
		});

		$this->hook("admin.column.file", function ( $option ) use($app) {
			$column = $option[0];
			$data = $option[1];
			$storage = new \Upload\Storage\FileSystem( $app->config("file.path") );
			$name = $column->name;
			if( isset($_FILES[$name]) ){
				$file = new \Upload\File( $name, $storage );
				if( $file->isOk() ) {
					$file->upload();
					$data->$name = $file->getNameWithExtension();
				}
			}
		});
	}

	public function applyHookColumn( $data ) {
		$columns = $this->table()->columns();
		for ($i = 0; $i < count($columns); $i++) {
			$col = $columns[$i];
			$this->applyHook( "admin.column." . $col->type, array( $col, $data ) );
		}
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
