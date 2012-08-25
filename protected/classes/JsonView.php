<?php defined('SYSPATH') or die('No direct script access.');

class JsonView
{
	/**************************************************************
	 *
	 *  使用特定function对数组中所有元素做处理
	 *  @param  string  &$array     要处理的字符串
	 *  @param  string  $function   要执行的函数
	 *  @return boolean $apply_to_keys_also     是否也应用到key上
	 *  @access public
	 *
	*************************************************************/
	public static function arrayRecursive(&$array, $function, $apply_to_keys_also = false)
	{
		static $recursive_counter = 0;
		if (++$recursive_counter > 1000) {
			die('possible deep recursion attack');
		}
		foreach ($array as $key => $value) {
			if (is_array($value) || is_object($value) ) {
				if (is_array($array)) {
					self::arrayRecursive($array[$key], $function, $apply_to_keys_also);
				} else {
					self::arrayRecursive($array->$key, $function, $apply_to_keys_also);
				}
			} else {
				if( is_array( $array ) )
					$array[$key] = $function($value);
				else 
					$array->$key = $function($value);
			}
	  
			if ($apply_to_keys_also && is_string($key)) {
				$new_key = $function($key);
				if ($new_key != $key) {
					$array[$new_key] = $array[$key];
					unset($array[$key]);
				}
			}
		}
		$recursive_counter--;
	}
		  
	/**************************************************************
	 *
	 *  将数组转换为JSON字符串（兼容中文）
	 *  @param  array   $array      要转换的数组
	 *  @return string      转换得到的json字符串
	 *  @access public
	 *
	*************************************************************/
	public static function JSON($array)
	{
		self::arrayRecursive($array, 'urlencode', true);
		$json = json_encode($array);
		return urldecode($json);
	}

	public function multcast_rsp( $rsp, $sessions )
	{
		$rsp['sessions'] = $sessions;
		$data = self::JSON( $rsp );
		if( Lib_Global::$zipFlag == 1 )
			$data = gzcompress( $data, 1 );
		$this->buff .= $data;
	}

	public function rsp( $rsp )
	{
		$this->multcast_rsp( $rsp, array( Lib_Global::$session ) );
	}

	public function rsp_err( $msg = '' )
	{
		if( $msg == '' )
			$msg = __('非法请求');
		$rsp['errMsg'] = $msg;
		return $this->rsp( $rsp );
	}
	
	protected $buff;

	public function __construct()
	{
		$this->buff = '';
	}

	public function display()
	{
		return $this->buff;
	}
	
	public function render()
	{
		return $this->buff;
	}

	public function reg( $group_infos ) {}

	public function unreg( $group_info ) {}

	public function broadcast_rsp( $rsp, $groupInfo=null ){}

	public function close( $session ) {}

	public function stat() {}

}
