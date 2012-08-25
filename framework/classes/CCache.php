<?php defined('SYSPATH') or die('No direct access');

/**
 * 缓存处理类
 * 
 * @author Andy Cai (huayicai@gmail.com)
 *
 */
class CCache
{
	protected $_type = 'file';	// 'file', 'memcache', 'redis', 'db'
	
	public function __construct()
	{
		//
	}
	
	/**
	 * 缓存
	 * 
	 * @param mixed $name 缓存名称
	 * @param mixed $data 缓存内容
	 * @param mixed $lifetime 过期
	 */
	public static function doCache($name, $data = NULL, $lifetime = NULL)
	{
		// Cache file is a hash of the name
		$file = sha1($name).'.txt';

		// Cache directories are split by keys to prevent filesystem overload
		$dir = Yi::$cache_dir.DIRECTORY_SEPARATOR.$file[0].$file[1].DIRECTORY_SEPARATOR;

		if ($lifetime === NULL)
		{
			// Use the default lifetime
			$lifetime = Yi::$cache_life;
		}

		if ($data === NULL)
		{
			if (is_file($dir.$file))
			{
				if ((time() - filemtime($dir.$file)) < $lifetime)
				{
					// Return the cache
					try
					{
						return unserialize(file_get_contents($dir.$file));
					}
					catch (Exception $e)
					{
						// Cache is corrupt, let return happen normally.
					}
				}
				else
				{
					try
					{
						// Cache has expired
						unlink($dir.$file);
					}
					catch (Exception $e)
					{
						// Cache has mostly likely already been deleted,
						// let return happen normally.
					}
				}
			}

			// Cache not found
			return NULL;
		}

		if ( ! is_dir($dir))
		{
			// Create the cache directory
			mkdir($dir, 0777, TRUE);

			// Set permissions (must be manually set to fix umask issues)
			chmod($dir, 0777);
		}

		// Force the data to be a string
		$data = serialize($data);

		try
		{
			// Write the cache
			return (bool) file_put_contents($dir.$file, $data, LOCK_EX);
		}
		catch (Exception $e)
		{
			// Failed to write cache
			return FALSE;
		}
	}
} // End CCache