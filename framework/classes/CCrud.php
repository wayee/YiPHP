<?php defined('SYSPATH') or die('No direct script access.');

/**
 * 封装简单的crud操作等
 * 
 * @author Andy Cai (huayicai@gmail.com)
 *
 */
class CCrud extends CModel
{
	public function __construct( $className, $tableName )
	{
		parent::__construct( $className, $tableName );
	}
	
	/**
	 * 创建记录
	 * @param object $obj
	 * @param string $onDuplicate = 'x=**, y=**'
	 */
	protected function create($obj, $onDuplicate=NULL)
	{
		if ( !$this->checkCachePrefix()) return 0;
		
		if ( empty($obj) ) return 0;
		
		$fields = array_keys( (array)$obj );
		$lastInsertId = $this->get_db()->add( $obj, $fields, $onDuplicate );
		if ($lastInsertId) {
			$primaryKey = $this->_primaryKey;
			$obj->$primaryKey = $lastInsertId;
			$this->get_cache()->set_obj( $this->_cachePrefix . $lastInsertId, $obj );
		}
		
		return $lastInsertId;
	}
	
	/**
	 * 根据id获取信息
	 * @param int $id
	 * @param array $fields
	 * @return object
	 */
	protected function get($id, $fields=NULL)
	{
		if ( !$this->checkCachePrefix()) return 0;
		
		$cacheKey = $this->_cachePrefix . $id;
		if( empty( $fields ) )
			$obj = $this->get_cache()->get_obj( $cacheKey );
		else
			$obj = $this->get_cache()->get_fields( $cacheKey, $fields );
		if( !empty( $obj ) )
			return $obj;
		$where = ' `id` = :id ';	
		$params['id'] = $id;
		$obj = $this->get_db()->fetch_obj( $where, $params );
		if( $obj )
		{
			$this->get_cache()->set_obj( $cacheKey, $obj );
			return $obj;
		}
		else
			return NULL;
	}
	
	/**
	 * 根据自己定义的key来获取数据
	 * 如：你可以获取$key = 'roleId' 和 $value=1000，即roleId=1000的数据
	 * 跟crud_get不同的是，crud_get 只能获取 id=xxx的数据
	 * @param id|string $value
	 * @param array $fields
	 * @param string $key
	 * @return object $obj
	 */
	protected function find($value, $fields=NULL, $key='id')
	{
		if ( !$this->checkCachePrefix()) return NULL;
		
		$cacheKey = $this->_cachePrefix . $value;
		if( empty( $fields ) )
			$obj = $this->get_cache()->get_obj( $cacheKey );
		else
			$obj = $this->get_cache()->get_fields( $cacheKey, $fields );
		if( !empty( $obj ) )
			return $obj;
		$where = ' `'.$key.'` = :'.$key.' ';	
		$params[$key] = $value;
		$obj = $this->get_db()->fetch_obj( $where, $params );
		if( $obj )
		{
			$this->get_cache()->set_obj( $cacheKey, $obj );
			return $obj;
		}
		else
			return NULL;
	}
	
	/**
	 * 获取列表，可以从缓存中获取
	 * $this->search()则不行
	 * @param array $idList = array(1, 2, 3);
	 * @param array $fields
	 * @param string $key
	 * @return array $list
	 */
	protected function findList( $idList, $fields=NULL, $key='id')
	{
		$list = array();
		
		foreach ($idList as $id) {
			$list[] = $this->crud_find($id, $fields, $key);
		}
		
		return $list;
	}
	
	/**
	 * 更新记录
	 * @param int $id
	 * @param array $params = array('x'=>'**')
	 * @return boolean
	 */
	protected function update($id, $params)
	{
		if ( !$this->checkCachePrefix()) return FALSE;
		
		if( !$params )
			return FALSE;
			
		$key = $this->_primaryKey;

		$where        = " `".$key."` = :".$key." ";
		$params[$key] = $id;
		$fields       = array_keys( $params );
		$return = $this->get_db()->update($fields, $params, $where);

		$cacheKey = $this->_cachePrefix . $id;
		$this->get_cache()->set_arr( $cacheKey, $params );
		
		return $return;
	}
	
	/**
	 * 更新记录
	 * @param int $id
	 * @param array $params = array('x'=>'**')
	 * @param $key 可以设定要条件的key
	 * @return boolean
	 */
	protected function udpateNB($id, $params, $key='id')
	{
		if ( !$this->checkCachePrefix()) return FALSE;
		
		if( !$params )
			return FALSE;

		$where        = " `".$key."` = :".$key." ";
		$params[$key] = $id;
		$fields       = array_keys( $params );
		$return = $this->get_db()->update($fields, $params, $where);

		$cacheKey = $this->_cachePrefix . $id;
		$this->get_cache()->set_arr( $cacheKey, $params );
		
		return $return;
	}
	
	/**
	 * 删除记录
	 * @param int $id
	 * @return boolean
	 */
	protected function remove($id)
	{
		if ( !$this->checkCachePrefix()) return FALSE;
		
		$key = $this->_primaryKey;
		
		$where = " `".$key."` = :".$key." ";
		$params[$key] = $id;
		$return = $this->get_db()->remove( $where, $params );
		$key = $this->_cachePrefix . $id;
		$this->get_cache()->del_obj( $key );
		
		return $return;
	}
	
	/**
	 * @param string $where
	 * @param array $params
	 * @param string $fields
	 * @return array 对象数组
	 */
	public function search( $where, $params, $fields='*', $orderBy=NULL, $limit=NULL )
	{
		return $this->get_db()->fetch_all( $where, $params, $fields, $orderBy, $limit );
	}
	
	/**
	 * 记录数量
	 * @param string $field
     * @param string $where
     * @param array $params
     * @return int
	 */
	public function count( $fields, $where, $params )
	{
		return $this->get_db()->count( $fields, $where, $params );
	}
	
	private function checkCachePrefix()
	{
		if ( !$this->_cachePrefix ) {
			throw new ErrorException(__('Model 必须指定缓存的关键字前缀  _cachePrefix'));
			return FALSE;
		}
		return TRUE;
	}
} // End CCrud