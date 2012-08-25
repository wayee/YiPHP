<?php 

defined('SYSPATH') or die('No direct script access.');

/// proxy rpc方法调用接口封装
class Lib_ProxyRpc
{
	private $_ip;
	private $_port;
	private $_socket;
	const HEAD_LEN = 4;

	public function __construct( $ip, $port )
	{
		$this->_ip   = $ip;
		$this->_port = $port;
	}

	private function connect()
	{
		$this->_socket = socket_create( AF_INET, SOCK_STREAM, SOL_TCP );
		socket_set_nonblock( $this->_socket );
		$conn_ret = @socket_connect( $this->_socket, $this->_ip, $this->_port );
		if( !$conn_ret )
		{
			$last_err = socket_last_error( $this->_socket );
			if( $last_err != 114 && $last_err != 115 )
			{
				Lib_SysUtil::log( 'proxy_rpc', 'connection refused: ' . $this->_ip . ':' . $this->_port . ' last: ' . $last_err );
				return false;
			}
		}
		switch( @socket_select( $r = array($this->_socket), $w = array($this->_socket), $f = array($this->_socket), 2 ) )
		{
			case 0:
				Lib_SysUtil::log( 'proxy_rpc', 'connect timeout: ' . $this->_ip . ':' . $this->_port );
				return false;
			case 1:
				Lib_SysUtil::log( 'proxy_rpc', 'connected: ' . $this->_ip . ':' . $this->_port );
				return true;
			default:
				Lib_SysUtil::log( 'proxy_rpc', 'connection refused: ' . $this->_ip . ':' . $this->_port . ' last: ' . socket_last_error( $this->_socket ) );
				return false;
		}
	}

	private function write_proxy_pack( $pack )
	{
		$length = self::HEAD_LEN + strlen($pack);
		$head   = pack( 'L', $length );
		$pack = $head . $pack;
		while(true)
		{ 
			$sent = @socket_write( $this->_socket, $pack, $length ); 
			if($sent === false)
			{
				$last_err = socket_last_error( $this->_socket );
				if( $last_err != 114 && $last_err != 115 )
				{
					Lib_SysUtil::log( 'proxy_rpc', 'write err. addr: ' . $this->_ip . ':' . $this->_port . ' last: ' . $last_err );
					return false;
				}
				continue; 
			}
			if($sent < $length)
			{ 
				$pack = substr($pack, $sent); 
				$length -= $sent; 
			}
			else
			{
				Lib_SysUtil::log( 'proxy_rpc', 'write ok. len: ' . $length );
				return true; 
			}
		} 
        return false;
	}

	private function read_proxy_pack()
	{
		$maxRead = 1024;

		$packLen = 0;
		$buff    = '';
		while( true )
		{
			$readSockets  = array( $this->_socket );
			$writeSockets = array();
			$errSockets   = array( $this->_socket );

			$eventNum = @socket_select( $readSockets, $writeSockets, $errSockets, 3 ); 
			switch( $eventNum )
			{
				case 0:
					Lib_SysUtil::log( 'proxy_rpc', 'read timeout, addr: ' . $this->_ip . ':' . $this->_port );
					return '';
				case 1:
					$data = @socket_read( $this->_socket, $maxRead, PHP_BINARY_READ ); 
					if( $data === false )
					{
						$last_err = socket_last_error( $this->_socket );
						if( $last_err != 114 && $last_err != 115 )
						{
							Lib_SysUtil::log( 'proxy_rpc', 'read err, addr: ' . $this->_ip . ':' . $this->_port . ' last: ' . $last_err );
							return '';
						}
						break;
					}
					$buff .= $data;
					break;
				default:
					$last_err = socket_last_error( $this->_socket );
					Lib_SysUtil::log( 'proxy_rpc', 'read err, addr: ' . $this->_ip . ':' . $this->_port . ' last: ' . $last_err );
					return '';
			}
			$totalLen = strlen( $buff );
			if( $totalLen >= self::HEAD_LEN )
			{
				$head = unpack( 'L', $buff );
				print_r( $head );
				if( !is_array( $head ) || !isset( $head[1] ) )
					return '';
				$packLen = $head[1];
				Lib_SysUtil::log( 'proxy_rpc', 'read head, packlen: ' . $packLen . ' total read: ' . $totalLen );
				if( $packLen <= self::HEAD_LEN || $packLen > $totalLen )
					return '';
				if( $packLen == $totalLen )
				{
					$ret = substr( $buff, 4, $packLen - self::HEAD_LEN );
					return $ret;
				}
			}
    	}
    }

	private function close()
	{
		socket_close( $this->_socket );
	}

	public function send( $pack )
	{
		if( !$this->connect() )
			return false;
		$write_ret = $this->write_proxy_pack( $pack );
		if($write_ret === false)
			return false; 
		$this->close();
		return true;
	}

	public function send_and_recv( $pack )
	{
		if( !$this->connect() )
			return '';
		$write_ret = $this->write_proxy_pack( $pack );
		if($write_ret === false)
			return ''; 
		$ret = $this->read_proxy_pack();
		$this->close();
		return $ret;
	}

} // End class

