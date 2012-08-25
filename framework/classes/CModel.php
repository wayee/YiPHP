<?php defined('SYSPATH') or die('No direct script access.');

/**
 * 抽象数据模型类
 * 
 * @author Andy Cai (huayicai@gmail.com)
 *
 */
abstract class CModel implements IModel
{
	private static $db;
	private static $cache;

	protected $_className;
	protected $_tableName;
	protected $_cachePrefix;
	protected $_primaryKey = 'id';
	
	protected function __construct( $className, $tableName )
	{
		$this->_className = $className;
		$this->_tableName = $tableName;
		if( !self::$db )
			self::$db = new Mod_Db_Proxy( DB_HOST, DB_LIB, $className, $tableName );
		self::$db->init( $className, $tableName );
		if( !self::$cache )
			self::$cache = new Mod_Redis_Proxy( CACHE_HOST, CACHE_PORT );
		self::$cache->init( $this->_className );
	}

	public function db( $tableName = '', $className='' )
	{
		if( !empty($tableName) )
			$this->_tableName = $tableName;
		if( !empty($className) )
			$this->_className = $className;
		self::$db->init( $this->_className, $this->_tableName );
		return self::$db;
	}

	public function cache( $className='' )
	{
		if( !empty($className) )
			$this->_className = $className;
		self::$cache->init( $this->_className );
		return self::$cache;
	}
} // End CModel