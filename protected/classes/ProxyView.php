<?php defined('SYSPATH') or die('No direct script access.');

class ProxyView
{
	private $_buffs = array();
	public function __construct()
	{
		parent::__construct();
	}

	public function display()
	{
		return $this->_buffs;
	}
	
	public function render()
	{
		return $this->buffs;
	}

	public function reg( $groupInfos ) // 注册
	{
		$buff = '';
		Lib_ProxyPack::pack_reg( $buff, $groupInfos );
		$this->_buffs[] = $buff;
	}

	public function unreg( $groupInfo )
	{
		$buff = '';
		Lib_ProxyPack::pack_reg( $buff, $groupInfo );
		$this->_buffs[] = $buff;
	}

	public function broadcast_rsp( $rsp, $groupInfo )
	{
		$buff = '';
		Lib_ProxyPack::pack_broadcast( $buff, $rsp, null, $groupInfo );
		$this->_buffs[] = $buff;
	}

	public function multcast_rsp( $rsp, $sessions )
	{
		$buff = '';
		Lib_ProxyPack::pack_broadcast( $buff, $rsp, $sessions );
		$this->_buffs[] = $buff;
	}

	public function rsp( $rsp )
	{
		$buff = '';
		Lib_ProxyPack::pack_broadcast( $buff, $rsp, array( Lib_Global::$session ) );
		$this->_buffs[] = $buff;
	}

	public function rsp_err( $msg = '' )
	{
		if( $msg == '' )
			$msg = __('非法请求');
		$rsp['errMsg'] = $msg;
		return $this->rsp( $rsp );
	}

	public function close( $session )
	{
		$buff = '';
		Lib_ProxyPack::pack_close( $buff, $session );
		$this->_buffs[] = $buff;
	}

	public function stat()
	{
		$buff = '';
		Lib_ProxyPack::pack_stat( $buff );
		$this->_buffs[] = $buff;
	}

} // End ProxyView
