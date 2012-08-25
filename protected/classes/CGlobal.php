<?php

class CGlobal
{
	const BROADCAST_PARTY_TYPE   = 1;
	const BROADCAST_VILLAGE_TYPE = 2;
	
	const CACHE_SELF_ZONE_KEY    = 'globalSelfZoneInfo';
	const CACHE_ID_2_ZONE_PREFIX = 'globalId2Zone';
	const CACHE_IP_2_ZONE_PREFIX = 'globalIp2Zone';	

	/// 上下文相关
	public static $session;	
	public static $param;
	public static $zipFlag;

	/// 角色相关
	private static $spUid;
	private static $sessionMod;

	/// 大区相关
	private static $cache;
	
	 // *****军队全局变量、常量和方法定义 start
	const ARMY_MAX_NUM_OF_CAMPS = 10;	// 兵营最大队伍数
	const ARMY_MAX_NUM_OF_ROLE = 5;		// 出征最大队伍数
	const ARMY_MAX_NUM_OF_FORT = 5;		// 要塞最大队伍数
	const ARMY_POS_ROLE	= 1;			// 出征
	const ARMY_POS_FORT = 2;			// 要塞
	public static $armyRoleDefaultInfo = array('id'=>0, 'pic'=>0, 'num'=>0, 'locked'=>1);
	public static $armyFortDefaultInfo = array('id'=>0, 'pic'=>0, 'num'=>0, 'locked'=>0);
	
	public static function army_validate_pos($pos)
	{
		$result = TRUE;
		if ( $pos != self::ARMY_POS_ROLE && $pos != self::ARMY_POS_FORT)
			$result = FALSE;
		
		return $result;
	}
	// *****军队全局变量、常量和方法定义 end 
	
	//允许角色道具升级的等级
	public static $ALLOW_UPGRADE_PROPS_LEVEL = array( 0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15 );

	public static function init( $context, $param, $isZip )
	{
		self::$session = json_decode( $context, true );
		self::$param   = $param;
		self::$zipFlag = $isZip;
	}
	
	public static function spUid()
	{
		if( !empty( self::$spUid) && strlen( self::$spUid ) == 32 )
			return self::$spUid;
		if( empty( self::$sessionMod ) )
 			self::$sessionMod = new Mod_Session();
		self::$spUid = self::$sessionMod->get_id_by_session( self::$session );
		return self::$spUid;
	}

	private static function get_cache()
	{
		if( !self::$cache )
			self::$cache = new Mod_Redis_Proxy( CONF_CACHE_HOST, CONF_CACHE_PORT );
		return self::$cache;
	}

	private static function read_zone_by_id( $id )
	{
		$zone = array();
		if( ( $handle = fopen( CONF_DIR . 'zone_config.csv', 'r') ) !== FALSE )
		{
			while (($data = fgetcsv($handle, 128)) !== FALSE)
			{
				if( $id == $data[0] )
				{
					$zone['id'] = $id;
					$zone['ip'] = ip2long( $data[1] );
					fclose($handle);
					return $zone;
				}
			}
			fclose($handle);
		}
		return $zone;
	}

	private static function read_zone_by_ip( $ip )
	{
		$zone = array();
		if( ( $handle = fopen( CONF_DIR . 'super_ip_list.csv', 'r') ) !== FALSE )
		{
			while (($data = fgetcsv($handle, 128)) !== FALSE)
			{
				if( $ip == $data[1] )
				{
					$zone['id'] = $data[0];
					$zone['ip'] = ip2long( $data[1] );
					fclose($handle);
					return $zone;
				}
			}
			fclose($handle);
		}
		return $zone;
	}

	private static function get_zone_by_id( $id )
	{
		$cache = self::get_cache();
		if( empty( $cache ) )
			return self::read_zone_by_id( $id );
		$key = self::CACHE_ID_2_ZONE_PREFIX . $id;
		$zone = array();
		if( !$cache->exists( $key ) )
		{
			$zone = self::read_zone_by_id( $id );
			if( is_array( $zone) )
				$cache->set_arr( $key, $zone );
		}
		else
			$zone = $cache->get_arr( $key );
		return $zone;
	}

	public static function get_zone_by_ip( $ip )
	{
		$cache = self::get_cache();
		if( empty( $cache ) )
			return self::read_zone_by_ip( $ip );
		$key  = self::CACHE_IP_2_ZONE_PREFIX . $ip;
		$zone = array();
		if( !$cache->exists( $key ) )
		{
			$zone = self::read_zone_by_id( $ip );
			if( $ip != 0 )
				$cache->set_arr( $key, $zone );
		}
		else
			$zone = $cache->get_arr( $key );
		return $zone;
	}

	public static function check_id_by_session( $id )
	{
		if( empty( self::$session ) )
			return false;
		
		$ip = long2ip( self::$session['connIp'] );
		$zone = self::get_zone_by_ip( $ip );
		if( !empty( $zone ) )
			return true;

		$sessionMod = new Mod_Session();
		$db_id = $this->get_id_by_session( self::$session );
		if( $db_id == 0 )
			return false;
		return $id == $db_id;
	}

	public static function check_zone( $zoneId )
	{
		$zoneInfo = self::get_zone_by_id($zoneId);
		if( empty( $zoneInfo ) )
			return false;
		return true;
	}

}
