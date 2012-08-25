<?php defined('SYSPATH') or die('No direct script access.');

/**
 * 为游戏开发使用的路由器
 * 
 * @author Andy Cai (huayicai@gmail.com)
 * 
 */
class CGameRoute
{
	public static function set()
	{
		// 命令行入口
		if ( Yi::$isCli ) {
			$reqStr   = $argv[1];
			$viewType = !empty($argv[2]) ? $argv[2] : 'json';
			$session  = !empty($argv[3]) ? $argv[3] : '';
			if ( isset($reqStr) && $reqStr )
				return self::req(0, $reqStr, $session, $viewType);
		}
		
		// http入口
		if ( !empty($_POST) ) $httpReqParam = $_POST;
		else $httpReqParam = $_GET;
		
		$reqStr   = isset($httpReqParam['reqStr']) ? $httpReqParam['reqStr'] : '';
		$isZip    = isset($httpReqParam['isZip']) ? $httpReqParam['isZip'] : 0;
		$viewType = isset($httpReqParam['view']) ? $httpReqParam['view'] : 'json';
		$session  = isset($httpReqParam['session']) ? $httpReqParam['session'] : '';
		if ( !empty( $reqStr ) )
			return self::req($isZip, $reqStr, $session, $viewType);
	
	}
	
	// 函数入口
	public static function req( $isZip, $reqStr='', $context='', $rsp_view_str='json' )
	{
		$isException = $rsp_view_str != 'proxy';
		if ( empty( $reqStr ) )	
		{
			throwException("error, req str is empty!\n", $isException);
			return '';
		}
	
		if( $isZip == 1 )
			$reqStr = gzuncompress( $reqStr );
		$req = json_decode( $reqStr, true );
		if ( empty( $req ) )
		{
			throwException("error, req isn't json str!\n", $isException);
			return '';
		}
	
		$view_file = APPPATH . 'view' . DS . $rsp_view_str . EXT;
		if ( !file_exists($view_file) ) 
		{
			throwException("error, '" . Lib_Debug::path($view_file) . "' isn't exist\n", $isException);
			return '';
		}
	
		$act      = $req['act'];
		$act_part = array();
		if ( empty($act) || !preg_match( '/^([0-9a-z_]+)\.([0-9a-z_]+)$/i', $act, $act_part ) || 
			substr($act_part[1], 0, 1) == '_' )  // 不允许应"_"为前缀的方法请求，如："map._enter"
		{
			throwException("error, act str error\n", $isException);
			return '';
		}
		
		$ctrl_file = APPPATH . 'ctrl' . DS . $act_part[1] . EXT;
		if ( !file_exists($ctrl_file) ) 
		{
			throwException("error, '" . Lib_Debug::path($ctrl_file) . "' isn't exist\n", $isException);
			return '';
		}
		
		// 加载配置文件
		require APPPATH . 'config/conf.php';
		
		// 设置本次会话的全局变量
		if( isset( $req['param'] ) )
			Lib_Global::init( $context, $req['param'], $isZip );
		else
			Lib_Global::init( $context, null, $isZip );
	
		$ctrlClassName				= CTRL_PREFIX . $act_part[1];
		$class 						= new ReflectionClass($ctrlClassName);
		if ($class->isAbstract())
		{
			throwException(__('Cannot create instances of abstract :controller',
				array(':controller' => $ctrlClassName)), $isException);
		}
		$ctrl = $class->newInstance();
		Facade::getInstance()->set_ctrl($ctrl);
		$ctrl->set_view($rsp_view_str);
		$view = $ctrl->get_view();
	
		if (method_exists($ctrl, $act_part[2]))
		{
			// 根据session上锁
			$lock = null;
			if( !empty( Lib_Global::$session ) )
			{
				$key  = 'session/' . Lib_Global::$session['connIdx'];
				$lock = Lib_AppUtil::lock($key);
				if( empty($lock) )
					$view->rsp_err( __('请稍缓操作') );
			}
			
			// 执行调用
			$class->getMethod('before')->invoke($ctrl);
			triggerHook('BEFORE_EXECUTE_ACTION');
			$class->getMethod($act_part[2])->invoke($ctrl);
			triggerHook('AFTER_EXECUTE_ACTION');
			$class->getMethod('after')->invoke($ctrl);
	
			// 解锁
			if( !empty( $lock ) )
				Lib_AppUtil::unlock($lock);
		}
		else
		{
			throwException("error, [ $CtrlClass :: $act_part[2] ] doesn't exist in ".Lib_Debug::path(APPPATH.$act_part[1]).EXT, $isException);
		}
		
		return $view->display();
	}
} // End CGameRoute