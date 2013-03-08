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
	 * @var bool
	 */
	protected $start = false;

	/**
	 * @var array
	 */
	protected $cache;

	/**
	 * Constructor
	 * @param  array $settings Associative array of application settings
	 */
	public function __construct( $settings = array() ) {
		$this->cache = array();
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
	 * @return  mix
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
		$this->start = true;
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
				$actions = $table->actions();
				for ($j = 0; $j < count($actions); $j++) {
					$this->action( $actions[$j] );
				}
				$actions = $table->multiActions();
				for ($j = 0; $j < count($actions); $j++) {
					$this->multiAction( $actions[$j] );
				}
				if( $table->permit("create") ) {
					$this->create( $table );
					$this->save( $table );
				}
				if( $table->permit("update") ) {
					$this->edit( $table );
					$this->update( $table );
				}
				if( $table->permit("delete") ) {
					$this->del( $table );
				}

			}
		}

		$root = $this->request()->getRootUri();
		for ($i = 0; $i < $len; $i++) {
			$table = $tables[$i];
			if( $table->permit("manage") ) {
				$table->url = $root . "/" . $table->name;
			}
		}

		$this->hookColumn();
		$this->run();
	}

	protected function cache( $method, $args ){
		$object = $args[0];
		$name = $method . ($object instanceof Admin\Action ? ("-" . $object->table->name) : "" ) . "-" . $object->name;
		if( !$this->start ) {
			$this->cache[ $name ] = $args;
			return true;
		} else if( isset( $this->cache[$name] ) ) {
			$args = $this->cache[$name];
			unset( $this->cache[$name] );
			call_user_func_array(array($this, $method), $args);
			return true;
		} else {
			return false;
		}
	}

	public function renderTemplate( $method ) {
		$name = $this->table()->name;
		$path = $this->config("templates.path");
		$temp = $name . "-" . $method . ".html.twig";

		$file = $path . '/'. $temp;
		if( file_exists($file) ) {
			$this->render( $temp );
			return;
		}

		$temp = $method . ".html.twig";
		$file = $path . '/'. $temp;
		if( file_exists($file) ) {
			$this->render( $temp );
			return;
		}
	}

	/**
	 * Router for index data
	 */
	public function index($table, $callable = null){
		$table = $this->db->table( $table );
		if( $this->cache(__FUNCTION__, func_get_args()) )
			return $this;

		$name = $table->name;
		$app = $this;
		$this->get("/" . $name . "(.:format)", function( $format = "html" ) use ($table, $app, $callable) {
			if( $format != "csv" && $format != "html" ) {
				return $app->pass();
			}
			$table->load();
			$app->table( $table );
			$req = $app->request();

			$table->conditions( $req->get() )
				->sort( $req->get("sort") );

			if( is_callable( $callable ) ) {
				call_user_func( $callable );
			} else {
				$app->data( 
					$table->all( $req->get("page") ) 
				);
			}	

			if( $format == "csv" ) {
				$app->csv();
			} else {
				$app->renderTemplate("list");
			}
		});
		return $this;
	}

	/**
	 * Router for create data
	 */
	public function create($table, $callable = null){
		$table = $this->db->table( $table );
		if( $this->cache(__FUNCTION__, func_get_args()) )
			return $this;

		$name = $table->name;
		$app = $this;
		$this->get("/" . $name . "/new", function() use ($table, $app, $callable) {
			$params = $app->request()->get();
			$table->load()->associate( $params );
			$app->table( $table );
			$app->data( (object)$params );
			if( is_callable( $callable ) ) {
				call_user_func( $callable );
			} 
			$app->renderTemplate("new");
		});
	}

	/**
	 * Router for save data
	 */
	public function save($table, $callable = null){
		$table = $this->db->table( $table );
		if( $this->cache(__FUNCTION__, func_get_args()) )
			return $this;

		$name = $table->name;
		$app = $this;
		$this->post("/" . $name . "/new", function() use ($table, $app, $callable) {
			$params = $app->request()->post();
			$table->load()->associate( $params );
			$app->table( $table );
			$data = (object)$params;
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
						$app->renderTemplate("new");
					}
				}
			} catch( \Exception $e ) {
				$app->flashNow( "error", $e->getMessage() );
				$app->renderTemplate("new");
			}
		});
	}

	/**
	 * Router for edit data
	 */
	public function edit($table, $callable = null){
		$table = $this->db->table( $table );
		if( $this->cache(__FUNCTION__, func_get_args()) )
			return $this;

		$name = $table->name;
		$app = $this;
		$this->get("/" . $name . "/:id", function($id) use ($table, $app, $callable) {
			$table->load();
			$app->table( $table );
			$data = $table->find( $id );
			if( !$data )
				return $app->notFound();

			$app->data( $data );
			$app->view()->setData( "_referrer", $app->request()->getReferrer() );
			$app->view()->setData( "params", $data );

			if( is_callable( $callable ) ) {
				call_user_func( $callable );
			} 
			$app->renderTemplate("edit");
		});
	}

	/**
	 * Router for update data
	 */
	public function update($table, $callable = null){
		$table = $this->db->table( $table );
		if( $this->cache(__FUNCTION__, func_get_args()) )
			return $this;

		$name = $table->name;
		$app = $this;
		$this->put("/" . $name . "/:id", function($id) use ($table, $app, $callable) {
			$table->load();
			$app->table( $table );
			$data = $table->find( $id );
			if( !$data )
				return $app->notFound();

			$req = $app->request();
			$ref = $req->post("_referrer");
			$app->view()->setData( "_referrer", $ref );

			$app->data( $data );
			$params = (object)$req->post();
			$app->view()->setData( "params", $params );

			try{
				$app->applyHookColumn( $params );
				if( is_callable( $callable ) ) {
					call_user_func( $callable );
				} else {
					if( $table->update( $id, $params ) ) {
						$app->flash("success", "更新成功!");
						$app->redirect( empty($ref) ? $table->url : $ref );
					} else {
						$app->flashNow( "error", "更新失败" );
						$app->renderTemplate("edit");
					}
				}
			} catch( \Exception $e ) {
				$app->flashNow( "error", $e->getMessage() );
				$app->renderTemplate("edit");
			}
		});
	}

	/**
	 * Router for delete data
	 */
	public function del($table, $callable = null){
		$table = $this->db->table( $table );
		if( $this->cache(__FUNCTION__, func_get_args()) )
			return $this;

		$name = $table->name;
		$app = $this;
		$this->delete("/" . $name . "/:id", function($id) use ($table, $app, $callable) {
			$table->load();
			$app->table( $table );
			$data = $table->find( $id );
			if( !$data )
				return $app->notFound();
			try{
				if( is_callable( $callable ) ) {
					call_user_func( $callable );
				} else {
					$app->flash("success", $table->delete( $id ) ? "删除成功!" : "删除失败");
				}
			} catch( \Exception $e ) {
				$app->flash( "error", $e->getMessage() );
			}
			$ref = $app->request()->getReferrer();
			$app->redirect( empty($ref) ? $table->url : $ref );
		});
	}

	/**
	 * Router for action
	 */
	public function action($action, $callable = null){
		if( $this->cache(__FUNCTION__, func_get_args()) )
			return $this;

		$table = $action->table;
		$name = $table->name;
		$app = $this;
		$pattern = "/" . $name . "/:id/" . $action->name;
		$this->get($pattern, function($id) use ($table, $action, $app, $callable) {
			$table->load();
			$app->table( $table );
			$data = $table->find( $id );
			if( !$data )
				return $app->notFound();

			$app->view()->setData( "_referrer", $app->request()->getReferrer() );
			$app->view()->setData( "action", $action );
			$app->view()->setData( "params", $data );
			$app->data( $data );
			$app->renderTemplate("action");
		});
		$this->post($pattern, function($id) use ($table, $action, $app, $callable) {
			$table->load();
			$app->table( $table );
			$data = $table->find( $id );
			if( !$data )
				return $app->notFound();

			$req = $app->request();
			$ref = $req->post("_referrer");
			$app->view()->setData( "_referrer", $ref );

			$app->data( $data );
			$params = (object)$req->post();
			$app->view()->setData( "action", $action );
			$app->view()->setData( "params", $params );

			try{
				if( is_callable( $action->callable ) ) {
					call_user_func( $action->callable, $table, $data, $params );
				}
				if( is_callable( $callable ) ) {
					call_user_func( $callable );
				} else {
					$app->flash("success", $action->alias . "成功!");
					$app->redirect( empty($ref) ? $table->url : $ref );
				}
			} catch( \Exception $e ) {
				$app->flashNow( "error", $e->getMessage() );
				$app->renderTemplate("action");
			}
		});
	}

	public function multiAction($action, $callable = null){
		if( $this->cache(__FUNCTION__, func_get_args()) )
			return $this;

		$table = $action->table;
		$name = $table->name;
		$app = $this;
		$pattern = "/" . $name . "/" . $action->name;
		$this->get($pattern, function() use ($table, $action, $app, $callable) {
			$table->load();
			$app->table( $table );
			$req = $app->request();
			$ids = $req->get("ids");
			if( !is_array($ids) || empty($ids) )
				return $app->notFound();

			$key = $table->key();
			$data = $table
				->conditions( array($key => $ids ) )
				->all();

			if( !$data )
				return $app->notFound();

			$app->view()->setData( "_referrer", $app->request()->getReferrer() );
			$app->view()->setData( "action", $action );
			$app->view()->setData( "params", (object)$req->get() );
			$app->data( $data );
			$app->renderTemplate("multi-action");
		});
		$this->post($pattern, function() use ($table, $action, $app, $callable) {
			$table->load();
			$app->table( $table );
			$req = $app->request();
			$ids = $req->post("ids");
			if( !is_array($ids) || empty($ids) )
				return $app->notFound();

			$key = $table->key();
			$data = $table
				->conditions( array($key => $ids ) )
				->all();

			if( !$data )
				return $app->notFound();

			$ref = $req->post("_referrer");
			$app->view()->setData( "_referrer", $ref );

			$app->data( $data );
			$params = (object)$req->post();
			$app->view()->setData( "action", $action );
			$app->view()->setData( "params", $params );

			try{
				if( is_callable( $action->callable ) ) {
					call_user_func( $action->callable, $table, $data, $params );
				}
				if( is_callable( $callable ) ) {
					call_user_func( $callable );
				} else {
					$app->flash("success", $action->alias . "成功!");
					$app->redirect( empty($ref) ? $table->url : $ref );
				}
			} catch( \Exception $e ) {
				$app->flashNow( "error", $e->getMessage() );
				$app->renderTemplate("multi-action");
			}
		});
	}

	public function csv($encode = null) {
		$res = $this->response();
		$table = $this->table();
		$res['Content-Type'] = "application/vnd.ms-excel";
		$res['Content-Disposition'] = "attachment;filename=".$table->name.".xls";
		$data = $table->tocsv( $this->data() );
		$data = mb_convert_encoding( $data, "gbk", "utf-8" );
		$this->halt( 200, $data );
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
