<?php defined('SYSPATH') or die('No direct script access.');

/**
 * 工具类
 * 
 * @author Andy Cai (huayicai@gmail.com)
 * 
 */
class CUtils
{
	public static function object2array($obj)
	{
		$array = array();
		foreach ($obj as $key=>$value) {
			$array[$key] = $value;
		}
		
		return $array;
	}
	
	public static function swap( &$a, &$b )
	{
		$t = $a;
		$a = $b ;
		$b = $t;
	}

	public static function hex_str_to_bin($x)
	{ 
		$s=''; 
		foreach( explode("\n", trim(chunk_split($x,2) ) ) as $h )
			$s .= chr( hexdec($h) ); 
		return($s); 
	} 

	public static function bin_to_hex_str($x)
	{ 
		$s=''; 
		foreach( str_split($x) as $c )
			$s .= sprintf( "%02X", ord($c) ); 
		return($s); 
	} 

	public static function unserialize($classname)
	{
		switch ( $classname )
		{
			default:
				break;
		}
		
		require_once APPPATH . 'vo' . DS . $classname . EXT;
	}

	public static function getPastHours($time)
	{
		return ($GLOBALS['nowTime'] - $time) / 3600;
	}

	public static function debug_page($arr)
	{
		echo "<div align=\"left\">";
		echo '<pre>';
		print_r($arr);
		echo '</pre>';
		echo "</div>";
	}

	public static function http_value( $key )
	{
		if( isset( $_GET[$key] ) )
			return $_GET[$key];
		if( isset( $_POST[$key] ) )
			return $_POST[$key];
		return '';
	}

	public static function get_micro_sec()
	{
		list($usec, $sec) = explode(" ", microtime());
		return ((float)$usec + (float)$sec);
	}
	
	public static function hash()
	{
		return sha1(self::getMicrotime());
	}   
	
	public static function encrypt($string)
	{
		return self::strRot13(base64_encode($string));
	}
	
	public static function decrypt($string)
	{
		return base64_decode(self::strRot13($string, true));
	}
	
	public static function strRot13($string, $decode=false)
	{
		$fromTable = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$toTable = 'kBh8OGr5PzXQ3DVbnTdfoswpujWSFxmMK0Z6yq2vLlaeYgcR7A9E4HJi1UNtCI';
		return $decode ? strtr($string, $toTable, $fromTable) : strtr($string, $fromTable, $toTable);
	}

	/*
	* 获取两个日期的时间差(秒数)
	* 日期2 - 日期1
	* 日期格式:  'Y-m-d H:i:s'
	* 返回秒数
	*/
	public static function diff_by_sec($date1, $date2)
	{
		$tmp1 = array();
		$tmp2 = array();

		preg_match('/(\d+)-(\d+)-(\d+) (\d+):(\d+):(\d+)/', $date1, $tmp1);
		preg_match('/(\d+)-(\d+)-(\d+) (\d+):(\d+):(\d+)/', $date2, $tmp2);

		$timestamp1 = mktime($tmp1[4], $tmp1[5], $tmp1[6], $tmp1[2], $tmp1[3], $tmp1[1]); 
		$timestamp2 = mktime($tmp2[4], $tmp2[5], $tmp2[6], $tmp2[2], $tmp2[3], $tmp2[1]); 
		
		return ($timestamp1-$timestamp2);
	}
	
	public static function str_is_int($str)
	{
		if( strlen($str) == 0 )
			return false;
		return preg_match('/^[0-9]*$/i',$str);   
	}
	
	/*
	 * 按输入概率输出成功或失败
	 * int : 成功比例
	 * int : 最大范围
	 * bool
	 */
	public static function probability($per, $max=100) 
	{   
		$per = intval($per); 
		$per = max($per,0);
		$per = min($per,$max);
		
		$key = rand(1,$max);
		if( $key <= $per )
			return true;
		return false; 
	}
	
	public static function mk_dir($strPath = '', $mode = 777) 
	{
		if (is_dir($strPath)) 
			return true;

		$pStrPath = dirname($strPath);
		if (!self::mk_dir($pStrPath, $mode)) 
			return false;
		
		return mkdir($strPath);
	}

	/**
	 * 生成验证图
	 * @param: $image_file 图片文件地址
	 * @return:  imgRndNum
	 */
	public static function make_verify_img($image_file)
	{
		/************************************* 新验证码 start *****************************************/
		$imgWidth = 56;
		$imgHeight = 20;
		$imgFontsize = 14;
		$astenNumber = 10;
		$rndNumSize = 4;
		$randNum = '';

		$im = imagecreate($imgWidth, $imgHeight);
		$white = imagecolorallocate($im, 255, 255, 255);
		$black = imagecolorallocate($im, 100, 100, 100);

		for ($i = 1; $i <= $astenNumber; $i++)
			imageString($im, 1, mt_rand(1, $imgWidth), mt_rand(1, $imgHeight), '*', imageColorAllocate($im, mt_rand(150,255), mt_rand(150,255), mt_rand(150,255)));

		//$font = dirname(__FILE__).DIRECTORY_SEPARATOR.'consola.ttf';
		$font = DATA_PATH . 'font/consola.ttf';

		$str = '1234567890';
		for ($i = 0; $i < strlen($str); $i++)
		{
			$imgRndNum .= $str{mt_rand(0, strlen($str)-1)};
		}

		for ($i = 0; $i < $rndNumSize; $i++)
		{
			$x = $i * ($imgFontsize) + 2;
			$y = 16;
			$n = mt_rand(0, 20);
			imagettftext($im, $imgFontsize, $n, $x, $y, $black, $font, $imgRndNum{$i});

			$randNum .= $imgRndNum{$i};
		}
		
		$black = imagecolorallocate($im, 130, 130, 130);
		$jamY1 = mt_rand(ceil($imgHeight/8), floor($imgHeight/8*7));
		$jamY2 = mt_rand(ceil($imgHeight/8), floor($imgHeight/8*7));
		$jamY3 = mt_rand(ceil($imgHeight/8), floor($imgHeight/8*7));
		$jamY4 = mt_rand(ceil($imgHeight/8), floor($imgHeight/8*7));
		$jamY5 = mt_rand(ceil($imgHeight/8), floor($imgHeight/8*7));

		$jamX = floor($imgWidth/4);
		$jamX1 = mt_rand(0, $jamX);
		$jamX2 = mt_rand($jamX1, $jamX*2);
		$jamX3 = mt_rand($jamX2, $jamX*3);
		
		imageline ($im,      0, $jamY1 , $jamX1, $jamY2 , $black);
		imageline ($im, $jamX1, $jamY2 , $jamX2, $jamY3 , $black);
		imageline ($im, $jamX2, $jamY3 , $jamX3, $jamY4 , $black);
		imageline ($im, $jamX3, $jamY4 , $imgWidth, $jamY5 , $black);

		imageline ($im,      0, $jamY1-1 , $jamX1, $jamY2-1 , $black);
		imageline ($im, $jamX1, $jamY2-1 , $jamX2, $jamY3-1 , $black);
		imageline ($im, $jamX2, $jamY3-1 , $jamX3, $jamY4-1 , $black);
		imageline ($im, $jamX3, $jamY4-1 , $imgWidth, $jamY5-1 , $black);
		
		imagepng($im, $image_file);
		imagedestroy($im);

		return $randNum;
		/************************************* 新验证码 end *******************************************/
	}

	public function myCurl($url, $param_post_str, $arr_param_get = NULL)
	{
		$ch = curl_init();
		if (!$ch)
			return false;
			
		$arr_param_get && $url .= '?' . http_build_query($arr_param_get);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);//timeout
		curl_setopt($ch, CURLOPT_POST, $param_post_str);
		
		$content = curl_exec($ch);
		curl_close($ch);
		
		return $content;
	}

	public static function log($file_name, $log)
	{
		if( !empty( $file_name ) AND !empty( $log ) )
		{
//			$log = '[' . date( 'Y-m-d H:i:s' ) . ']{' .  posix_getpid() . '}' . $log . "\r\n";
			$log = '[' . date( 'Y-m-d H:i:s' ) . ']{' .  '}' . $log . "\r\n";
			$file_path = LOG_DIR . $file_name;
			$file_handle = @fopen($file_path, 'ab');
			@fwrite($file_handle, $log);
			@fclose($file_handle);
		}
	}
	
	public static function redirect($uri = '', $method = 'location', $httpResponseCode = 302)
	{
		if ( ! preg_match('#^https?://#i', $uri))
		{
			$uri = SITE_URL . $uri;
		}

		switch($method)
		{
			case 'refresh'	: header("Refresh:0;url=".$uri);
				break;
			default			: header("Location: ".$uri, TRUE, $httpResponseCode);
				break;
		}
		exit;
	}
	
} // End CUtils