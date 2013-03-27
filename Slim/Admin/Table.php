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
	 * @var array types mapper
	 */
	public static $types = array(
		"bit" => "number",
		"int" => "number",
		"tinyint" => "number",
		"smallint" => "number",
		"mediumint" => "number",
		"bigint" => "number",
		"decimal" => "number",
		"float" => "number",
		"double" => "number",
		"char" => "text",
		"varchar" => "text",
		"binary" => "text",
		"varbinary" => "text",
		"text" => "textarea",
		"tinytext" => "textarea",
		"mediumtext" => "textarea",
		"longtext" => "textarea",
		"blob" => "textarea",
		"tinyblob" => "textarea",
		"mediumblob" => "textarea",
		"longblob" => "textarea",
		"enum" => "select",
		"set" => "checkgroup",
		"date" => "text",
		"datetime" => "text",
		"timestamp" => "text",
		"time" => "text",
		"year" => "text",
	);

	/**
	 * @var number
	 */
	public $per_size = 20;

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
	public $url;

	/**
	 * @var ActionManager
	 */
	protected $actions;
	protected $multi_actions;

	/**
	 *
	 * @var mix
	 */
	protected $cache;

	/**
	 *
	 * @var mix
	 */
	protected $key;

	/**
	 * @var string
	 */
	protected $sort;
	protected $sort_sql = "";

	protected $groupby;
	protected $groupby_sql = "";
	protected $groupby_sql2 = "";

	/**
	 * @var Pager
	 */
	protected $pager;
	protected $pager_sql = "";

	/**
	 * @var array
	 */
	protected $defaults = array();

	/**
	 * @var array
	 */
	protected $conditions = array();
	protected $options = array();
	protected $conditions_sql = "";
	protected $search = null;

	/**
	 * @var array
	 */
	protected $filters;

	/**
	 * @var array
	 */
	protected $has_many;
	protected $belong_to;

	/**
	 * Constructor
	 *
	 * @param mix $name The table name
	 */
	public function __construct( $name, $settings = array() )
	{
		$this->alias = ucfirst( $name );
		$this->filters = array();
		$this->has_many = array();
		$this->belong_to = array();
		$this->actions = new ActionManager;
		$this->multi_actions = new ActionManager;
		parent::__construct( $name, $settings );
		$this->childClass = "\\Slim\\Admin\\Column";
	}

	/**
	 * @return conneciton from db
	 */
	protected function conn(){
		$db = $this->config("db");
		return $db ? $db->conn() : null;
	}

	/**
	 * Configure column
	 *
	 * @param  string $name The name of the column
	 * @param  string|array $settings  If a string, the name of the setting to set or retrieve. Else an associated array of setting names and values
	 *
	 * @return \Slim\Admin\Column
	 */
	public function column( $name, $settings = array() )
	{
		if( func_num_args() < 3 && is_string($settings) ) {
			$settings = array( "label" => $settings );
			return $this->child( $name, $settings );
		}
		return call_user_func_array(array($this, "child"), func_get_args());
	}

	/**
	 * Get columns
	 *
	 * @return array
	 */
	public function columns()
	{
		return $this->childrenList;
	}

	/**
	 * Action
	 *
	 */
	public function action($name, $settings, $callable = null) {
		return $this->actions->action($this, $name, $settings, $callable);
	}

	public function actions() {
		return $this->actions->actions();
	}

	public function multiAction($name, $settings, $callable = null) {
		return $this->multi_actions->action($this, $name, $settings, $callable);
	}

	public function multiActions() {
		return $this->multi_actions->actions();
	}

	/**
	 * urlFor
	 *
	 * @param array $qs query options
	 *
	 * @return url string
	 *
	 */
	public function urlFor( $qs = array() )
	{
		$path = $this->url;
		if( !is_array($qs) ) {
			if(is_object($qs)){
				$key = $this->key();
				if( $key ) {
					$qs = $qs->$key;
					if(is_array($qs) && !empty($qs)) {
						$qs = $qs[0];
					}
				}
			}
			$path .= "/" . $qs;
			$qs = array();
		}

		if( func_num_args() > 1 ) {
			$args = array_slice( func_get_args(), 1 );
			$len = count($args);
			for ($i = 0; $i < $len; $i++) {
				$arg = $args[$i];
				if( is_array($arg) ) {
					$qs = array_merge( $qs, $arg );
				} else {
					$path .= "/" . $arg;
				}
			}
		}

		$tmp = array();
		foreach ($qs as $key => $val) {
			if( !is_null($val) && $val !== "" ) {
				if( $key == "format" ) {
					$path .= "." . $val;
				} else {
					if( is_array( $val ) ) {
						foreach ($val as $v) {
							$tmp[] = $key . "[]=" . urlencode((string)$v);
						}
					} else {
						$tmp[] = $key . "=" . urlencode((string)$val);
					}
				}
			}
		}
		$qs = implode( $tmp, "&" );
		return empty( $qs ) ? $path : ( $path . "?" . $qs );
		return $path;
	}

	public function urlForPage( $page )
	{
		if( !$page )
			return "javascript:void(0);";
		return $this->urlFor( array(
			"page" => $page,
			"sort" => $this->sort(),
		), $this->options() );
	}

	public function urlForSort( $name )
	{
		$col = $this->column( $name );
		return $this->urlFor( array(
			"sort" => $col->order == 1 ? "-" . $col->name : $col->name
		), $this->options() );
	}

	public function urlForExport( $format = "xls" )
	{
		return $this->urlFor( array(
			"format" => $format,
			"sort" => $this->sort(),
			"page" => $this->pager()->page,
		), $this->options() );
	}

	/**
	 * Association
	 *
	 */
	public function associate( $data ) {
		if( empty( $data ) ) {
			return $this;
		}
		if( !empty( $this->has_many ) ) {
			foreach ( $this->has_many as $kkk => $o ) {
				$key = $o["lockey"];
				$ids = array_map(function($v) use ($key) {
					return $v->$key;
				}, array_filter($data, function($v) use ($key) { 
					return $v->$key; 
				}));
				if( count( $ids ) ) {
					$dd = $o["table"]->countBy($o["key"], $ids);
					$len = count($data);
					for ($i = 0; $i < $len; $i++) {
						$d = $data[$i];
						$d->$kkk = isset($dd[ $d->$key ]) ? $dd[ $d->$key ] : 0;
					}
				}
			}
		}
		if( !empty( $this->belong_to ) ) {

			foreach ( $this->belong_to as $kkk => $o ) {
				$key = $kkk;
				$dkey = "_" . $kkk;
				$ids = array_map(function($v) use ($key) {
					return $v->$key;
				}, array_filter($data, function($v) use ($key) { 
					return $v->$key; 
				}));
				if( count( $ids ) ) {
					$dd = $o["table"]->pair($o["key"], $ids, true);
					$len = count($data);
					for ($i = 0; $i < $len; $i++) {
						$d = $data[$i];
						$d->$dkey = isset($dd[ $d->$key ]) ? $dd[ $d->$key ] : "";
					}
				}
			}
		}
		return $this;
	}

	public function fetchForFilter()
	{
		$values = $this->conditions();
		if( !empty( $this->filters ) ) {
			foreach ( $this->filters as $key => $o ) {
				$data = array();
				$table = $o["table"];
				$scope = $o["scope"];
				$scopekey = $o["scopekey"];
				if( $scope ) {
					if(isset($values[$scope]) && !is_array($values[$scope]) && $values[$scope] !== "") {
						$table->load();
						$k = $scopekey ? $scopekey : $scope;
						$table->conditions( array( $k => $values[$scope] ) );
						$data = $table->pair($o["display"]);
					}
				} else {
					$table->load();
					$data = $table->pair($o["display"]);
				}
				$this->column( $key, array("type"=>"select", "extra"=>$data) );
			}
		}
		return $this;
	}

	public function has( $table, $locDisplay, $remoteKey, $locKey = null )
	{
		$this->has_many[ $locDisplay ] = array(
			"table" => $table, 
			"key" => $remoteKey, 
			"lockey" => $locKey ? $locKey : $this->key(),
		);
	}

	public function belong( $table, $locKey, $remoteDisplay )
	{
		$this->belong_to[ $locKey ] = array(
			"table" => $table, 
			"key" => $remoteDisplay, 
		);
	}

	public function urlForFilter($name, $value)
	{
		return $this->urlFor($this->options(), array($name => $value));
	}

	/**
	 * Filters
	 */
	public function filter( $table, $locKey, $remoteDisplayKey, $scope = null, $scopekey = null )
	{
		$this->filters[ $locKey ] = array(
			"table" => $table, 
			"display" => $remoteDisplayKey, 
			"scope" => $scope,
			"scopekey" => $scopekey,
		);
	}

	public function filters()
	{
		$columns = $this->columns();
		$filters = array();
		$len = count($columns);
		$options = $this->options();
		for ($i = 0; $i < $len; $i++) {
			$col = $columns[$i];
			if( $col->type == "select" && $col->permit("filter") ) {
				$name = $col->name;
				$value = isset($options[$name]) ? $options[$name] : "";
				$all = $value === "";
				$defaults = isset($this->defaults[$name]) ? $this->defaults[$name] : array();
				$defaults = is_array( $defaults ) ? $defaults : array( $defaults );
				$def_len = count($defaults);
				if( $def_len == 1 ) {
					continue;
					$extra = array();
				} else {
					$extra = array( array( $this->urlForFilter($name, ""), "所有" . $col->label, $all ) );
				}
				$defaults = self::array_pair( $defaults, null, null );
				if(is_array($col->extra)) {
					$len2 = count( $col->extra );
					for ($j = 0; $j < $len2; $j++) {
						$dd = $col->extra[$j];
						if( !$def_len || isset( $defaults[ $dd[0] ] ) )
							$extra[] = array($this->urlForFilter($name, $dd[0]), $dd[1], !$all && $value == $dd[0]);
					}
				}
				$filters[] = $extra;
			}
		}
		return $filters;
	}

	/**
	 * Format data
	 *
	 */
	public function format( $data )
	{
		if( is_object($data) ) {
			$data = array( $data );
		}
		$columns = $this->columns();
		$len = count($columns);

		//set default format
		if( $len && !$columns[0]->formatter && $this->permit("update") ) {
			$columns[0]->formatter = function($table, $col, $row, $val) {
				return array("link", $val, $table->urlFor($row));
			};
		}
		//set default format for has many
		foreach ($this->has_many as $key => $o) {
			$col = $this->column($key);
			if(!$col->formatter) {
				$ttt = $o["table"];
				$kkk = $o["key"];
				$lockey = $o["lockey"];
				$col->formatter = function($table, $col, $row, $val) use( $ttt, $kkk, $lockey ) {
					return array("link", $val, $ttt->urlFor(array($kkk => $row->$lockey)));
				};
			}
		}

		//set default format for belong
		foreach ($this->belong_to as $key => $o) {
			$col = $this->column($key);
			if(!$col->formatter) {
				$ttt = $o["table"];
				$kkk = "_" . $key;
				$col->formatter = function($table, $col, $row, $val) use( $ttt, $kkk ) {
					$key = $table->key();
					return array("link", empty($row->$kkk) ? $val : $row->$kkk, $ttt->urlFor((object)array($key => $val)));
				};
			}
		}

		$cols = array();
		for ($i = 0; $i < $len; $i++) {
			$col = $columns[$i];
			if( $col->type == "select" && !$col->formatter ) {
				$col->formatter = function($table, $col, $row, $val) {
					$k = "_" . $col->name;
					return isset($row->$k) ? $row->$k : $val;
				};
			} else if( $col->type == "bool" && !$col->formatter ) {
				$col->formatter = function($table, $col, $row, $val) {
					return $val ? "是" : "否";
				};
			}
			if( $col->formatter ) {
				$cols[] = $col;
			}
		}
		$len = count( $data );
		$len2 = count( $cols );
		for ($i = 0; $i < $len; $i++) {
			$dd = $data[$i];
			for ($j = 0; $j < $len2; $j++) {
				$col = $cols[$j];
				$name = $col->name;
				$fname = $col->fname();
				$val = isset($dd->$name) ? $dd->$name : null;
				if( is_string($col->formatter) ) {
					$dd->$fname = array($col->formatter, $val);
				}else if( is_callable($col->formatter) ) {
					$dd->$fname = call_user_func( $col->formatter, $this, $col, $dd, $val );
				}
			}
		}
	}

	/**
	 * Load colums config from database
	 * 
	 * @param  array $data Not fetch data from $conn If data give.
	 *
	 * @return array loaded data..
	 *
	 */
	public function load( $data = array() )
	{
		$ar = array();
		if( func_num_args() ) {
			if( is_array( $data ) ) {
				for ($i = 0; $i < count($data); $i++) {
					$this->column($data[$i][0])->def($data[$i][1]);
				}
			}
			return $this;
		} else {
			$conn = $this->conn();
			if( $conn ) {
				if ( !$this->cache ) {
					$res = $conn->fetchAll("DESCRIBE " . $this->name);
					$res = array_map( function($col) {
						$res = $col["Type"];
						preg_match("/^[\w]+/", $res, $type);
						$type = strtolower( $type[0] );
						preg_match("/\(([^)]+)/", $res, $extra);
						if( !empty( $extra ) ) {
							$extra = $extra[1];
							$tag = substr( $extra, 0, 1 );
							if( $tag == "'" || $tag == "\"" ) {
								$extra = preg_split("/".$tag."\s*[,]\s*".$tag."/", preg_replace("/^".$tag."|".$tag."$/i", "", $extra) );
							} else {
								$extra = preg_split("/\s*[,]\s*/", $extra );
							}
						} else {
							$extra = null;
						}
						$_type = $type;
						$type = isset( Table::$types[$type] ) ? Table::$types[$type] : "text";
						if( $_type == "tinyint" && $extra && $extra[0] == 1 ) {
							$type = "bool";
						}
						if( $type == "number" ) {
							$extra = $extra && isset($extra[0]) ? ($extra && isset($extra[1]) ? pow(0.1, $extra[1]) : 1) : "any";
						} else if( $type == "select" || $type == "checkgroup" ) {
							$extra = array_map( function( $v ){
								return array( $v, $v );
							}, $extra );
						}
						return array( $col["Field"], array(
							"key" => $col["Key"] == "PRI",
							"_type" => $_type,
							"type" => $type,
							"extra" => $extra,
						) );
					}, $res );
					$this->cache = $res;
				}
				return $this->load( $this->cache );
			} else {
				//throw not conn
			}
		}
		return $this;
	}

	public function key()
	{
		if( !$this->key ) {
			$columns = $this->columns();
			$len = count($columns);
			for ($i = 0; $i < $len; $i++) {
				$col = $columns[$i];
				if( $col->key() ) {
					$this->key = $col->name;
					break;
				}
			}
			if( !$this->key ) {
				$conn = $this->conn();
				if( $conn ) {
					$key = $conn->fetchValue("SELECT `COLUMN_NAME` FROM `information_schema`.`COLUMNS` WHERE (`TABLE_SCHEMA` = DATABASE()) AND (`TABLE_NAME` = ?)  AND (`COLUMN_KEY` = 'PRI');", $this->name);
					if( $key )
						$this->key = $this->column( $key, "key", true )->name;
				}
			}
		}
		return $this->key;
	}

	public function sort( $name = null )
	{
		if( !func_num_args() ) {
			return $this->sort;
		}
		$sort = null;
		$asc = false;
		if( $name ) {
			if( $name && substr($name, 0, 1) == "-" ) {
				$name = substr($name, 1);
				$asc = true;
			}
			$columns = $this->columns();
			$len = count( $columns );
			for ($i = 0; $i < $len; $i++) {
				$col = $columns[$i];
				if( $name == $col->name ) {
					$col->order = $asc ? -1 : 1;
					$sort = $name;
				}
			}
		}

		if( !$sort ) {
			$key = $this->key();
			if( $key ) {
				$sort = $key;
				$asc = false;
			}
		} else {
			$this->sort = $asc ? ("-" . $sort) : $sort;
		}

		if( $sort ) {
			$this->sort_sql = " ORDER BY `" . $sort . "` " . ($asc ? "ASC" : "DESC");
		}
		return $this;
	}

	public function groupby( $name = null )
	{
		if( !func_num_args() ) {
			return $this->groupby;
		}
		$this->groupby = $name;
		$this->groupby_sql = is_null($name) ? "" : "( SELECT *,count(`".$name."`) `".$name."_num` FROM ( select * from `".$this->name."` ORDER BY `".$this->key()."` desc ) t1 GROUP BY `".$name."` ) `".$this->name."`";
	}

	public function pager( $page = null )
	{
		if( !func_num_args() ) {
			return $this->pager;
		}
		$conn = $this->conn();
		$num = 0;
		if( $conn ) {
			$num = $conn->fetchValue("SELECT count(*) FROM " 
				. (empty( $this->groupby_sql ) ? "`".$this->name."`" : $this->groupby_sql ) . $this->conditions_sql, $this->conditions);
		}
		$this->pager = new Pager( $page, $num, $this->config("per_size") );
		$this->pager_sql = " LIMIT :__size OFFSET :__offset";
		return $this;
	}

	public function search()
	{
		if( !func_num_args() ) {
			return $this->search ? $this->search[count($this->search) - 1] : null;
		}
		$this->search = func_get_args();
		return $this;
	}

	public function options( $name = null )
	{
		if( !func_num_args() ) {
			return $this->options;
		}
		return isset($this->options[$name]) ? $this->options[$name] : null;
	}

	/**
	 * Default conditions
	 *
	 */
	public function defaults( $ar = array() ) {
		if( func_num_args() ) {
			foreach ($ar as $key => $val) {
				if(!is_null($val)){
					if( is_array($val) ) {
						$ll = count($val);
						if( $ll != 0 ) {
							if( $ll == 1 )
								$val = $val[0];
							$this->defaults[$key] = $val;
						}
					} else {
						$this->defaults[$key] = $val;
					}
				}
			}
			return $this;
		}
		return $this->defaults;
	}

	public function conditions( $conditions = array() )
	{
		if( !func_num_args() ) {
			return $this->conditions;
		}

		$defaults = $this->defaults();
		$conditions = array_merge( $this->conditions, $conditions );
		$columns = $this->columns();
		$len = count( $columns );
		$where = array();
		$ar = array();
		$options = array();
		for ($i = 0; $i < $len; $i++) {
			$col = $columns[$i];
			$name = $col->name;
			if( isset( $conditions[$name] ) && $conditions[$name] !== "") {
				$val = $conditions[$name];
				$options[$name] = $val;
				if( isset($defaults[$name]) ) {
					$val2 = $defaults[$name];
					if( is_array($val2) ){
						if( !in_array($val, $val2) ) {
							$val = $val2;
							$options[$name] = "";
						}
					} else if( $val2 != $val ) {
						$val = $val2;
						$options[$name] = $val;
					}
				}
				$ar[$name] = $val;
				$where[] = "(`" . $name . "` ".(is_array( $val ) ? "IN" : "=")." :" . $name . ")";
			} else if( isset($defaults[$name]) ) {
				$val = $defaults[$name];
				$ar[$name] = $val;
				$where[] = "(`" . $name . "` ".(is_array( $val ) ? "IN" : "=")." :" . $name . ")";
			}
		}

		$sql = array();
		if( $this->search && !empty($conditions["q"])) {
			$q = $conditions["q"];
			$options["q"] = $q;
			$q = "%" . $q . "%";
			$count = count($this->search) - 1;
			for ($i = 0; $i < $count; $i++) {
				$name = $this->search[$i];
				$ar[$name] = $q;
				$sql[] = "`" . $name . "` like :" .$name;
			}
		}

		$this->options = $options;

		$c = array();
		if( !empty($where) )
			$c[] = implode(" AND ", $where);
		if( !empty($sql) )
			$c[] = "(" . implode(" OR ", $sql) . ")";

		$this->conditions_sql = empty($c) ? "" : " WHERE " . implode(" AND ", $c);
		$this->conditions = $ar;
		return $this;
	}

	/**
	 * Query
	 *
	 */
	public function countBy( $key, $ids )
	{
		$conn = $this->conn();
		$data = array();
		if( $conn ) {
			$this->conditions(array( $key => $ids ) );
			$data = $conn->fetchPairs( "SELECT `".$key."`, count(`".$key."`) `num` FROM `".$this->name."`" . $this->conditions_sql . " GROUP BY `".$key."` " . $this->sort_sql, $this->conditions);
		}
		return $data;
	}

	public function pair( $display, $ids = null, $pair = false )
	{
		$conn = $this->conn();
		$data = array();
		if( $conn ) {
			$key = $this->key();
			if( $key ) {
				if( $ids ) {
					$this->conditions(array( $key => $ids ) );
				}
				if( $pair ) {
					$data = $conn->fetchPairs( "SELECT `".$key."`,`".$display."` FROM `".$this->name."`" . $this->conditions_sql . $this->sort_sql, $this->conditions);
				} else {
					$data = $conn->fetchAll( "SELECT `".$key."`,`".$display."` FROM `".$this->name."`" . $this->conditions_sql . $this->sort_sql, MYSQLI_NUM, $this->conditions);
				}
			}
		}
		return $data;
	}

	protected function dictionary()
	{
		$columns = $this->columns();
		$len = count($columns);
		$data = array();
		for ($i = 0; $i < $len; $i++) {
			$col = $columns[$i];
			if( $col->type == "select" ) {
				$data[$col->name] = self::array_pair( $col->extra );
			}
		}
		return $data;
	}

	public function all( $page = 1 )
	{
		$this->pager( $page );
		$conn = $this->conn();
		$data = array();
		if( $conn ) {
			$conditions = $this->conditions;
			$pager = $this->pager;
			if( $pager ) {
				$conditions["__size"] = $pager->per_size;
				$conditions["__offset"] = $pager->offset;
			}
			$sql = "SELECT * FROM " . (empty( $this->groupby_sql ) ? "`".$this->name."`" : $this->groupby_sql ) . $this->conditions_sql . $this->sort_sql . $this->pager_sql;
			$data = $conn->fetchAll($sql, MYSQLI_ASSOC, $conditions);
			$this->fetchForFilter();
			$dict = $this->dictionary();
			for ($i = 0; $i < count($data); $i++) {
				$dd = $data[$i];
				foreach ($dict as $key => $ar) {
					$val = isset($dd[$key]) ? $dd[$key] : null;
					$dd["_" . $key] = isset($ar[$val]) ? $ar[$val] : $val;
				}
				$data[$i] = (object)$dd;
			}
			$this->associate( $data );
		}
		return $data;
	}

	public function find( $id )
	{
		$conn = $this->conn();
		if( $conn ) {
			$key = $this->key();
			if( !$key || empty($id) )
				return null;
			$this->conditions( array($key => $id) );
			$sql = "SELECT * FROM `" . $this->name . "`" . $this->conditions_sql;
			$data = $conn->fetchOne($sql, MYSQLI_ASSOC, $this->conditions);
			if( $data ) {
				$this->conditions($data);
				$this->fetchForFilter();
				return (object)$data;
			}
		}
		return null;
	}

	public function create( $values, $ignorePermission = false )
	{
		$conn = $this->conn();
		if( $conn ) {
			$data = array();
			$values = is_object($values) ? (array)$values : $values;
			$columns = $this->columns();
			$len = count($columns);
			$defaults = $this->defaults();
			for ($i = 0; $i < $len; $i++) {
				$col = $columns[$i];
				$name = $col->name;
				if( ($ignorePermission || $col->permit("create")) && !$col->key() && isset($values[$name]) ) {
					$data[$name] = is_array($values[$name]) ? implode(",", $values[$name]) : $values[$name];
				}
				if( isset($defaults[$name]) ) {
					$def = $defaults[$name];
					$data[$name] = is_array($def) ? 
						( isset($data[$name]) && in_array($data[$name], $def) ? $data[$name] : $def[0] ) 
						: $def;
				}
			}
			return $conn->save( $this->name, $data );
		}
		return null;
	}

	public function update( $id, $values, $ignorePermission = false )
	{
		$conn = $this->conn();
		if( $conn ) {
			$data = array();
			$key = $this->key();
			if( !$key || empty($id) )
				return null;
			$data[ $key ] = $id;
			$values = is_object($values) ? (array)$values : $values;
			$columns = $this->columns();
			$len = count($columns);
			$defaults = $this->defaults();
			for ($i = 0; $i < $len; $i++) {
				$col = $columns[$i];
				$name = $col->name;
				if( ($ignorePermission || $col->permit("update")) && !$col->key() && isset($values[$name]) ) {
					$data[$name] = is_array($values[$name]) ? implode(",", $values[$name]) : $values[$name];
					if( isset($defaults[$name]) ) {
						$def = $defaults[$name];
						$data[$name] = is_array($def) ? 
							( in_array($data[$name], $def) ? $data[$name] : $def[0] ) 
							: $def;
					}
				}
			}
			return $conn->save( $this->name, $data );
		}
		return null;
	}

	public function delete( $id )
	{
		$conn = $this->conn();
		if( $conn ) {
			$data = array();
			$key = $this->key();
			if( !$key || empty($id) )
				return null;

			$this->conditions( array($key => $id) );
			$sql = "DELETE FROM `" . $this->name . "`" . $this->conditions_sql;
			return $conn->query($sql, $this->conditions);
		}
		return null;
	}

	public function toxls( $data ) {
		$columns = $this->columns();
		$len = count( $data );
		$len2 = count($columns);
		$ddd = array();
		$this->format($data);
		$ar = array();
		for ($j = 0; $j < $len2; $j++) {
			$col = $columns[$j];
			if( $col->permit("export") ) {
				$ar[] = $col->label;
			}
		}
		$ddd[] = implode("\t ", $ar);

		for ($i = 0; $i < $len; $i++) {
			$ar = array();
			$dd = $data[$i];
			for ($j = 0; $j < $len2; $j++) {
				$col = $columns[$j];
				$name = $col->name;
				$fname = $col->fname();
				$sname = "_" . $name;
				if( $col->permit("export") ) {
					$d = isset($dd->$name) ? $dd->$name : "";
					$ar[] = str_replace(array("\n", "\r\n"), " ", isset($dd->$sname) ? $dd->$sname : $d);
				}
			}
			$ddd[] = implode("\t ", $ar);
		}
		return implode("\n", $ddd);
	}
}

?>
