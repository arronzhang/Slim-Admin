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
	 * @var number
	 */
	public $per_size = 15;

	/**
	 * @var sort
	 */
	protected $sort;
	protected $sort_sql = "";

	/**
	 * @var page
	 */
	protected $pager;
	protected $pager_sql = "";

	/**
	 * @var conditions
	 */
	protected $conditions = array();
	protected $conditions_sql = "";

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
	 * Constructor
	 *
	 * @param mix $name The table name
	 */
	public function __construct( $name, $settings = array() )
	{
		$this->alias = ucfirst( $name );
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
	public function has( $table, $remoteKey, $locDisplayKey )
	{
	}

	public function belong( $table, $locKey, $remoteDisplayKey )
	{
	}

	/**
	 * Format data
	 *
	 */
	public function format( $data )
	{
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
			return $data;
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
		$filters = array();
		for ($i = 0; $i < $len; $i++) {
			$col = $columns[$i];
			$name = $col->name;
			if( isset( $conditions[$name] ) && $conditions[$name] !== "") {
				$filters[$name] = $conditions[$name];
				$where[] = "(`" . $name . "` ".(is_array( $conditions[$name] ) ? "IN" : "=")." :" . $name . ")";
			}
		}
		$this->conditions_sql = empty($where) ? "" : " WHERE " . implode(" AND ", $where);
		$this->conditions = $filters;
		return $this;
	}

	public function all( $ignoreAssociation = false )
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

			for ($i = 0; $i < count($data); $i++) {
				$data[$i] = (object)$data[$i];
			}
		}
		return $data;
	}

	public function find( $id, $ignoreAssociation = false )
	{
		$conn = $this->conn();
		if( $conn ) {
			$key = $this->key();
			if( !$key || empty($id) )
				return null;
			$this->conditions( array($key => $id) );
			$sql = "SELECT * FROM `" . $this->name . "`" . $this->conditions_sql;
			$data = $conn->fetchOne($sql, MYSQLI_ASSOC, $this->conditions);
			if( $data )
				return (object)$data;
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
