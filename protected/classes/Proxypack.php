<?php defined('SYSPATH') or die('No direct script access.');

/// proxy rpc方法包封装，详细格式见proxy的c++代码
class Lib_ProxyPack 
{
	private static $REG_METHOD_ID       = 1;
	private static $UNREG_METHOD_ID     = 2;
	private static $REQ_METHOD_ID       = 3;
	private static $BROADCAST_METHOD_ID = 4;
	private static $CLOSE_METHOD_ID     = 5;
	private static $STAT_METHOD_ID      = 7;

	public static function pack_reg( &$buff, $groupInfos ) // 注册
	{
		if( empty( $groupInfos ) || !is_array( $groupInfos ) )
			return;
		$groupNum = count( $groupInfos );
		$buff .= pack( 'SS', self::$REG_METHOD_ID, $groupNum );
		foreach( $groupInfos as $info )
			$buff .= pack( 'SL', $info['type'], $info['groupId'] );
	}

	public static function pack_unreg( &$buff, $groupInfo )
	{
		if( empty( $groupInfo ) || !is_array( $groupInfo ) )
			return;
		$buff .= pack( 'SSL', self::$UNREG_METHOD_ID, $groupInfo['type'], $groupInfo['groupId'] );
	}

	public static function pack_req( &$buff, $req, $isZip )
	{
		$cookieSize = 0;
		$workerReqMethodId = 1;
		$buff .= pack( 'SSLLSLSS', self::$REQ_METHOD_ID, $cookieSize, 0,0,0,0, $workerReqMethodId, $isZip );
		$buff .= $req;
	}

	public static function pack_broadcast( &$buff, $rsp, $sessions=null, $groupInfo=null )
	{
		if( empty( $rsp ) || !is_array( $rsp ) )
			return;
		$data = json_encode( $rsp );
		if( Lib_Global::$zipFlag == 1 )
			$data = gzcompress( $data, 1 );
		$sessionNum = 0;
		if( is_array( $sessions ) )
			$sessionNum = count( $sessions );
		$type    = 0;
		$groupId = 1;
		if( is_array( $groupInfo ) )
		{
			$type    = $groupInfo['type'];
			$groupId = $groupInfo['groupId'];
		}
		$buff .= pack( 'SSLS', self::$BROADCAST_METHOD_ID, $type, $groupId, $sessionNum );
		if( is_array( $sessions ) )
		{
			foreach( $sessions as $session )
				$buff .= pack( 'LLSL', $session['connIdx'], $session['connIp'], $session['connPort'], $session['connTime'] );
		}
		$buff .= $data;
	}

	public static function pack_close( &$buff, $session )
	{
		if( empty( $session ) || !is_array( $session ) )
			return;
		$buff .= pack( 'SLLSL', self::$CLOSE_METHOD_ID, $session['connIdx'], $session['connIp'], $session['connPort'], $session['connTime'] );
	}

	public static function pack_stat( &$buff )
	{
		$buff .= pack( 'S', self::$STAT_METHOD_ID );
	}

} // End class
