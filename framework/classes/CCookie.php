<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Cookie 帮助类
 *
 * @author Andy Cai (huayicai@gmail.com)
 * 
 */
class CCookie
{
	public static $salt = NULL;
	public static $expiration = 0;
	public static $path = '/';
	public static $domain = NULL;
	public static $secure = FALSE;
	public static $httponly = FALSE;
	
	public static function get($key, $default = NULL)
	{
		if ( ! isset($_COOKIE[$key]))
		{
			// The cookie does not exist
			return $default;
		}

		// Get the cookie value
		$cookie = $_COOKIE[$key];

		// Find the position of the split between salt and contents
		$split = strlen(self::salt($key, NULL));

		if (isset($cookie[$split]) AND $cookie[$split] === '~')
		{
			// Separate the salt and the value
			list ($hash, $value) = explode('~', $cookie, 2);

			if (self::salt($key, $value) === $hash)
			{
				// CCookie signature is valid
				return $value;
			}

			// The cookie signature is invalid, delete it
			self::delete($key);
		}

		return $default;
	}

	public static function set($name, $value, $expiration = NULL)
	{
		if ($expiration === NULL)
		{
			// Use the default expiration
			$expiration = self::$expiration;
		}

		if ($expiration !== 0)
		{
			// The expiration is expected to be a UNIX timestamp
			$expiration += time();
		}

		// Add the salt to the cookie value
		$value = self::salt($name, $value).'~'.$value;

		return setcookie($name, $value, $expiration, self::$path, self::$domain, self::$secure, self::$httponly);
	}

	public static function delete($name)
	{
		// Remove the cookie
		unset($_COOKIE[$name]);

		// Nullify the cookie and make it expire
		return setcookie($name, NULL, -86400, self::$path, self::$domain, self::$secure, self::$httponly);
	}

	public static function salt($name, $value)
	{
		// Require a valid salt
		if ( ! self::$salt)
		{
			throw new Lib_CException('A valid cookie salt is required. Please set self::$salt.');
		}

		// Determine the user agent
		$agent = isset($_SERVER['HTTP_USER_AGENT']) ? strtolower($_SERVER['HTTP_USER_AGENT']) : 'unknown';

		return sha1($agent.$name.$value.self::$salt);
	}

} // End CCookie