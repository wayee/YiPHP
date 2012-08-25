<?php defined('SYSPATH') or die('No direct script access.');

/**
 * 封装Reids操作类
 * 
 * @author Andy Cai (huayicai@gmail.com)
 *
 */
class CReidsProxy
{
	public  $redis;
	private $_isStartPmult = false;	//是否已经开始批量提交redis命令
	private $_className = '';	    //获取对象的类名
	
	public function __construct( $ip, $port )
	{
		try{
			$this->redis = new Redis();
			$this->redis->pconnect( $ip, $port );
		}
		catch (Exception $e){
		CUtils::log( 'redis', '[construct]catch connect err: ' . $e->getMessage() );
		}
	}
	
	public function __destruct()
	{
		$this->redis->close();
	}

	public function init( $className )
	{
		$this->_className = $className;
	}

	private function arr_to_obj( $arr )
	{
		if( empty( $arr ) )
			return null;
		if( $this->_className != '' )
		{
			try {
				$obj = new $this->_className();
				foreach( $obj as $k => $v ) {
					if ( isset($arr[$k]) ) {
						$obj->$k = $arr[$k];
					} else {
						CUtils::log( 'redis', '[Could not find the key: '.$k.' in '.get_class($obj).' ]' );
					}
				}
			} catch (Exception $e) {
				CUtils::log( 'redis', '['.print_r($arr, TRUE).']catch arr_to_obj err: ' . $e->getMessage() );
			}
			return $obj;
		}
		else
			return (object)$arr;
	}

	public function exists( $key )
	{
		return $this->redis->exists($key);
	}
	
	public function get_obj( $key )
	{
		$arr = $this->redis->hGetAll($key);
		return $this->arr_to_obj( $arr );
	}

	public function get_arr($key)
	{
		return $this->redis->hGetAll($key);
	}
	
	public function get_objs($keys, $fields = null )
	{
		$this->redis->multi(Redis::PIPELINE);
		foreach ($keys as $key)
		{
			if (is_array($fields) && count($fields) > 0)
				$this->redis->hMGet($key, $fields);
			else
				$this->redis->hGetAll($key);
		}
		$ret = $this->redis->exec();
		if( !is_array( $ret ) )
			return null;
		$objs = array();
		foreach( $ret as $arr )
		{
			if( !empty( $arr ) )
				$objs[] = $this->arr_to_obj( $arr );
		}
		return $objs;
	}
	
	public function get_fields($key, $fields )
	{
		$this->redis->multi(Redis::PIPELINE);
		$this->exists( $key );
		$this->redis->hMGet( $key, $fields );
		$ret = $this->redis->exec();
		if( empty( $ret ) || !isset($ret[0]) || empty( $ret[0] ) || !isset($ret[1]) || empty($ret[1]) )
			return null;
		$arr = $ret[1];
		return $this->arr_to_obj( $arr );
	}
	
	public function get_field($key, $field)
	{
		return $this->redis->hGet($key, $field);
	}
	
	public function set_arr($key, $arr)
	{
		return $this->redis->hMset($key, $arr);
	}
	
	public function set_obj($key, $obj)
	{
		return $this->redis->hMset($key, (array)$obj);
	}
	
	public function del_obj($key)
	{
		return $this->redis->delete($key);
	}
	
	public function set($key, $value)
	{
		return $this->redis->set($key, $value);
	}
	
	public function get($key)
	{
		return $this->redis->get($key);
	}
	
	public function lPush($key, $value)
	{
		return $this->redis->lPush($key, $value);
	}
		
	public function pmulti()
	{
		if ($this->_isStartPmult == false)
		{
			$this->_isStartPmult = true;
			return $this->redis->multi(Redis::PIPELINE);
		}
	}
	
	public function pexec()
	{
		if ($this->_isStartPmult == true)
		{
			$ret = $this->redis->exec();
			$this->_isStartPmult = false;
			return $ret;
		}
	}
	
	public function transaction_begin()
	{
		return $this->redis->multi(Redis::MULTI);
	}
	
	public function transaction_commit()
	{
		return $this->redis->exec();
	}
	
} // End CRedisProxy