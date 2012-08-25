<?php defined('SYSPATH') or die('No direct script access.');

/**
 * 钩子实现
 * 
 * @author Andy Cai (huayicai@gmail.com)
 * 
 */
class CHook
{
	private $_hooks = array();
	
	public function __construct()
	{
	}
	
	/**
	 * 注册钩子
	 * @param $event 钩子的名称
	 * @param $callback 回调的方法，如："Ctrl_Map::enter"
	 */
	public function register($event, $callback)
	{
		$this->_hooks[$event][] = $callback;
	}
	
	/**
	 * 触发钩子
	 * @param $event 钩子的名称
	 * @param $args 参数数组
	 */
	public function trigger($event, $args=array())
	{
		if ( !isset($this->_hooks[$event]) ) {
			return FALSE;
		}
		
		$hooks = $this->_hooks[$event];
		if (is_array($hooks) && count($hooks) > 0) {
			foreach ($hooks as $hook) {
				$ref = explode("::", $hook);
				
				$className = $ref[0];
				$class = new $className();
				$method = $ref[1];
				
// 				$funcReflector = new ReflectionMethod( $className, $method );
// 				$funcReflector->invokeArgs( $class, $args );
				
				call_user_method_array($method, $class, $args);
			}
		}
		return TRUE;
	}
} // End CHook