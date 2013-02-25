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
	public $pageSize = 15;

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
					$res = $this->conn()->fetchAll("DESCRIBE " . $this->name);
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
	}

	public function all( $page, $sort = null, $filters = array(), $ignoreAssociation = false )
	{
	}

	public function pager( $page, $filters = array() )
	{
	}

	public function find( $id, $ignoreAssociation = false )
	{
	}

	public function create( $values, $ignorePermission = false )
	{
	}

	public function update( $id, $values, $ignorePermission = false )
	{
	}

	public function delete( $id, $ignorePermission = false )
	{
	}
}

?>
