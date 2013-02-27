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

	/**
	 * @var Pager
	 */
	protected $pager;
	protected $pager_sql = "";

	/**
	 * @var array
	 */
	protected $conditions = array();
	protected $conditions_sql = "";

	/**
	 * @var array
	 */
	protected $belong;

	/**
	 * Constructor
	 *
	 * @param mix $name The table name
	 */
	public function __construct( $name, $settings = array() )
	{
		$this->alias = ucfirst( $name );
		$this->belong = array();
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
		}
		return $this->child( $name, $settings );
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
				if( $key )
					$qs = $qs->$key;
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
				if( is_array( $val ) ) {
					foreach ($val as $v) {
						$tmp[] = $key . "[]=" . urlencode($v);
					}
				} else {
					$tmp[] = $key . "=" . urlencode($val);
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
		), $this->conditions() );
	}

	public function urlForSort( $name )
	{
		$col = $this->column( $name );
		return $this->urlFor( array(
			"sort" => $col->order == 1 ? "-" . $col->name : $col->name
		), $this->conditions() );
	}

	/**
	 * Association
	 *
	 */

	public function associate( $values = array() )
	{
		if( !empty( $this->belong ) ) {
			foreach ( $this->belong as $key => $o ) {
				$data = array();
				$table = $o["table"];
				$scope = $o["scope"];
				$scopekey = $o["scopekey"];
				if( $scope ) {
					if(isset($values[$scope]) && $values[$scope] !== "") {
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

	public function has( $table, $remoteKey, $locDisplayKey )
	{
	}

	public function belong( $table, $locKey, $remoteDisplayKey, $scope = null, $scopekey = null )
	{
		$this->belong[ $locKey ] = array(
			"table" => $table, 
			"display" => $remoteDisplayKey, 
			"scope" => $scope,
			"scopekey" => $scopekey,
		);
	}

	public function urlForFilter($name, $value)
	{
		return $this->urlFor($this->conditions, array($name => $value));
	}

	/**
	 * Filters
	 */
	public function filters()
	{
		$columns = $this->columns();
		$filters = array();
		$len = count($columns);
		for ($i = 0; $i < $len; $i++) {
			$col = $columns[$i];
			if( $col->type == "select" && $col->permit("filter") ) {
				$name = $col->name;
				$value = isset($this->conditions[$name]) ? $this->conditions[$name] : "";
				$all = $value === "";
				$extra = array( array( $this->urlForFilter($name, ""), "所有" . $col->label, $all ) );
				if(is_array($col->extra)) {
					$len2 = count( $col->extra );
					for ($j = 0; $j < $len2; $j++) {
						$dd = $col->extra[$j];
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
		if( $len && !$columns[0]->formatter ) {
			$columns[0]->formatter = function($table, $row, $val) {
				return array("link", $val, $table->urlFor($row));
			};
		}

		$cols = array();
		for ($i = 0; $i < $len; $i++) {
			$col = $columns[$i];
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
				$val = isset($dd->$name) ? $dd->$name : null;
				if( is_string($col->formatter) ) {
					$dd->$name = array($col->formatter, $val);
				}else if( is_callable($col->formatter) ) {
					$dd->$name = call_user_func( $col->formatter, $this, $dd, $val );
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

	public function pager( $page = null )
	{
		if( !func_num_args() ) {
			return $this->pager;
		}
		$conn = $this->conn();
		$num = 0;
		if( $conn ) {
			$num = $conn->fetchValue("SELECT count(*) FROM " 
				. $this->name . $this->conditions_sql, $this->conditions);
		}
		$this->pager = new Pager( $page, $num, $this->config("per_size") );
		$this->pager_sql = " LIMIT :__size OFFSET :__offset";
		return $this;
	}

	public function conditions( $conditions = array() )
	{
		if( !func_num_args() ) {
			return $this->conditions;
		}
		$conditions = array_merge( $this->conditions, $conditions );
		$columns = $this->columns();
		$len = count( $columns );
		$where = array();
		$ar = array();
		for ($i = 0; $i < $len; $i++) {
			$col = $columns[$i];
			$name = $col->name;
			if( isset( $conditions[$name] ) && $conditions[$name] !== "") {
				$ar[$name] = $conditions[$name];
				$where[] = "(`" . $name . "` ".(is_array( $conditions[$name] ) ? "IN" : "=")." :" . $name . ")";
			}
		}
		$this->conditions_sql = empty($where) ? "" : " WHERE " . implode(" AND ", $where);
		$this->conditions = $ar;
		return $this;
	}

	/**
	 * Query
	 *
	 */

	public function pair( $display )
	{
		$conn = $this->conn();
		$data = array();
		if( $conn ) {
			$key = $this->key();
			if( $key ) {
				$data = $conn->fetchAll( "SELECT `".$key."`,`".$display."` FROM `".$this->name."`" . $this->conditions_sql . $this->sort_sql, MYSQLI_NUM, $this->conditions);
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

	public function all()
	{
		$conn = $this->conn();
		$data = array();
		if( $conn ) {
			$conditions = $this->conditions;
			$pager = $this->pager;
			if( $pager ) {
				$conditions["__size"] = $pager->per_size;
				$conditions["__offset"] = $pager->offset;
			}
			$sql = "SELECT * FROM `" . $this->name . "`" . $this->conditions_sql . $this->sort_sql . $this->pager_sql;
			$data = $conn->fetchAll($sql, MYSQLI_ASSOC, $conditions);
			$this->associate( $this->conditions );
			$dict = $this->dictionary();
			for ($i = 0; $i < count($data); $i++) {
				$dd = $data[$i];
				foreach ($dict as $key => $ar) {
					$val = isset($dd[$key]) ? $dd[$key] : null;
					$dd["_" . $key] = $val;
					$dd[$key] = isset($ar[$val]) ? $ar[$val] : $val;
				}
				$data[$i] = (object)$dd;
			}
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
				$this->associate( $data );
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
			for ($i = 0; $i < $len; $i++) {
				$col = $columns[$i];
				$name = $col->name;
				if( ($ignorePermission || $col->permit("create")) && !$col->key() && isset($values[$name]) ) {
					$data[$name] = is_array($values[$name]) ? implode(",", $values[$name]) : $values[$name];
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
			for ($i = 0; $i < $len; $i++) {
				$col = $columns[$i];
				$name = $col->name;
				if( ($ignorePermission || $col->permit("update")) && !$col->key() && isset($values[$name]) ) {
					$data[$name] = is_array($values[$name]) ? implode(",", $values[$name]) : $values[$name];
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

}

?>
