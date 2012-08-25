<?php

if ( ! defined('YI_START_TIME')) {
	define('YI_START_TIME', microtime(TRUE));
}

if ( ! defined('YI_START_MEMORY')) {
	define('YI_START_MEMORY', memory_get_usage());
}

error_reporting(E_ALL & ~E_DEPRECATED); // (PHP >= 5.3 E_ALL & ~E_DEPRECATED), (PHP < 5.3 E_ALL | E_STRICT), E_ALL ^ E_NOTICE
ini_set('display_errors', 'On');
ini_set('date.timezone','Asia/Shanghai');
ini_set('unserialize_callback_func', 'spl_autoload_call');

$application = 'protected';
$framework = 'framework';

define('DS', DIRECTORY_SEPARATOR);
define('EXT', '.php');
define('APPPATH', DOCROOT.DS.$application.DS);
define('SYSPATH', DOCROOT.DS.$framework.DS);

unset($application, $framework);

/**
 * 框架的帮助类
 * 
 * @author Andy Cai (huayicai@gmail.com)
 * 
 */
class Yi
{
	const VERSION  = '1.0.0';
	const CODENAME = 'YiTong';
	
	const PRODUCTION  = 10;
	const STAGING     = 20;
	const TESTING     = 30;
	const DEVELOPMENT = 40;
	
	public static $lang = 'zh-cn';
	public static $charset = 'utf-8';
	public static $environment = Yi::DEVELOPMENT;
	public static $isCli = FALSE;
	public static $isWindows = FALSE;
	public static $magicQuotes = FALSE;
	public static $safeMode = FALSE;
	public static $baseUrl = '/';
	public static $themePath = DOCROOT;
	public static $theme = 'default';
	public static $indexFile = 'index.php';
	public static $errors = FALSE;
	public static $shutdownErrors = array(E_PARSE, E_ERROR, E_USER_ERROR);
	public static $config = array();
	public static $hooks = array();
	public static $cache_dir;
	public static $cache_life = 60;
	public static $caching = FALSE;
	
	private static $_mvcClasses = array();
	private static $_classes = array();
	protected static $_init;
	
	public static function init(array $settings = NULL)
	{
		if (self::$_init) {
			// Do not allow execution twice
			return;
		}

		// Yi is now initialized
		self::$_init = TRUE;

		// Start an output buffer
		ob_start();

		if (isset($settings['errors'])) {
			// Enable error handling
			self::$errors = (bool) $settings['errors'];
		}

		if (isset($settings['theme'])) {
			self::$theme = (string) $settings['theme'];
		}

		if (self::$errors === TRUE) {
			// Enable exception handling, adds stack traces and error source.
			set_exception_handler(array('Yi', 'handleException'));

			// Enable error handling, converts all PHP errors to exceptions.
			set_error_handler(array('Yi', 'handleError'));
		}

		// Enable the Yi shutdown handler, which catches E_FATAL errors.
		register_shutdown_function(array('Yi', 'handleShutdown'));

		if (ini_get('register_globals')) {
			// Reverse the effects of register_globals
			self::globals();
		}

		// Determine if we are running in a command line environment
		self::$isCli = (PHP_SAPI === 'cli');

		// Determine if we are running in a Windows environment
		self::$isWindows = (DIRECTORY_SEPARATOR === '\\');

		// Determine if we are running in safe mode
		self::$safeMode = (bool) ini_get('safe_mode');

		if (isset($settings['charset'])) {
			// Set the system character set
			self::$charset = strtolower($settings['charset']);
		}
		if (isset($settings['lang'])) {
			self::$lang = strtolower(str_replace(array(' ', '_'), '-', $settings['lang']));
		}

		if (function_exists('mb_internal_encoding')) {
			// Set the MB extension encoding to the same character set
			mb_internal_encoding(self::$charset);
		}
		
		if (isset($settings['cache_dir']))
		{
			if ( ! is_dir($settings['cache_dir']))
			{
				try
				{
					// Create the cache directory
					mkdir($settings['cache_dir'], 0755, TRUE);

					// Set permissions (must be manually set to fix umask issues)
					chmod($settings['cache_dir'], 0755);
				}
				catch (Exception $e)
				{
					throw new CException('Could not create cache directory :dir',
						array(':dir' => CDebug::path($settings['cache_dir'])));
				}
			}

			// Set the cache directory path
			Yi::$cache_dir = realpath($settings['cache_dir']);
		}
		else
		{
			// Use the default cache directory
			Yi::$cache_dir = APPPATH.'cache';
		}

		if ( ! is_writable(Yi::$cache_dir))
		{
			throw new CException('Directory :dir must be writable',
				array(':dir' => CDebug::path(Yi::$cache_dir)));
		}

		if (isset($settings['cache_life']))
		{
			// Set the default cache lifetime
			Yi::$cache_life = (int) $settings['cache_life'];
		}

		if (isset($settings['caching']))
		{
			// Enable or disable internal caching
			Yi::$caching = (bool) $settings['caching'];
		}

		if (isset($settings['baseUrl'])) {
			// Set the base URL
			self::$baseUrl = rtrim($settings['baseUrl'], '/').'/';
		}

		if (isset($settings['indexFile'])) {
			// Set the index file
			self::$indexFile = trim($settings['indexFile'], '/');
		}

		// Determine if the extremely evil magic quotes are enabled
		self::$magicQuotes = (bool) get_magic_quotes_gpc();

		// Sanitize all request variables
		$_GET    = self::sanitize($_GET);
		$_POST   = self::sanitize($_POST);
		$_COOKIE = self::sanitize($_COOKIE);
	}
	
	public static function app()
	{
		return CFacade::getInstance();
	}
	
	public static function import($alias)
	{
		$segments = explode('.', $alias);
		$className = $segments[count($segments)-1];
		$filePath = implode('/', $segments);
		$filePath = $filePath . EXT;
		self::$_classes[$className] = $filePath;
	}
	
	public static function importMvc($className, $path)
	{
		self::$_mvcClasses[$className] = $path;
	}

	public static function autoload($className)
	{
		if (isset(self::$_coreClasses[$className])) {
			include(SYSPATH.DS.self::$_coreClasses[$className]);
		} else if (isset(self::$_mvcClasses[$className])) {
			include(APPPATH.DS.self::$_mvcClasses[$className]);
		}else if (isset(self::$_classes[$className])) {
			include(APPPATH.DS.'classes'.DS.self::$_classes[$className]);
		} else {
			include(APPPATH.DS.'classes'.DS.$className.EXT);
			return class_exists($className,FALSE) || interface_exists($className,FALSE);
		}
		return TRUE;
	}
	
	public static function httpResponse($status='404')
	{
		ob_start();
		$view_file = SYSPATH . 'views' . DS . 'error' . $status . EXT;
		if ( is_file($view_file) ) {
			include $view_file;
		}
		echo ob_get_clean();
	}
	
	public static function globals()
	{
		if (isset($_REQUEST['GLOBALS']) OR isset($_FILES['GLOBALS']))
		{
			// Prevent malicious GLOBALS overload attack
			echo "Global variable overload attack detected! Request aborted.\n";

			// Exit with an error status
			exit(1);
		}

		// Get the variable names of all globals
		$global_variables = array_keys($GLOBALS);

		// Remove the standard global variables from the list
		$global_variables = array_diff($global_variables, array(
			'_COOKIE',
			'_ENV',
			'_GET',
			'_FILES',
			'_POST',
			'_REQUEST',
			'_SERVER',
			'_SESSION',
			'GLOBALS',
		));

		foreach ($global_variables as $name)
		{
			// Unset the global variable, effectively disabling register_globals
			unset($GLOBALS[$name]);
		}
	}
	
	/**
	 * 递归清理输入变量：
	 *
	 * - magic quotes 开启，删除斜线
	 * - 规范所有换行为 LF
	 *
	 * @param   mixed  any variable
	 * @return  mixed  sanitized variable
	 */
	public static function sanitize($value)
	{
		$magic_quotes = (bool) get_magic_quotes_gpc();
		
		if (is_array($value) OR is_object($value))
		{
			foreach ($value as $key => $val)
			{
				// Recursively clean each value
				$value[$key] = self::sanitize($val);
			}
		}
		elseif (is_string($value))
		{
			if ($magic_quotes === TRUE)
			{
				// Remove slashes added by magic quotes
				$value = stripslashes($value);
			}

			if (strpos($value, "\r") !== FALSE)
			{
				// Standardize newlines
				$value = str_replace(array("\r\n", "\r"), "\n", $value);
			}
		}

		return $value;
	}
	
	/**
	 * 缓存
	 * 
	 * @param mixed $name 缓存名称
	 * @param mixed $data 缓存内容
	 * @param mixed $lifetime 过期
	 */
	public static function cache($name, $data = NULL, $lifetime = NULL)
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
	
	/**
	 * 主题模版路径
	 */
	public static function themePath()
	{
		return self::$themePath . DS . 'themes' . DS . self::$theme . DS;
	}
	
	/**
	 * 异常接管
	 * 
	 * @param mixed $exception
	 * @return void
	 */
	public static function handleException($exception)
	{
		// disable error capturing to avoid recursive errors
		restore_error_handler();
		restore_exception_handler();
		
		if ($exception instanceof CHttpException) {
			ob_start();
			$view_file = SYSPATH . 'views' . DS . 'error' . $exception->statusCode . EXT;
			if ( is_file($view_file) ) {
				include $view_file;
			}
			echo ob_get_clean();
			exit(1);
		} else {
			CException::handler($exception);
		}
	}
	
	/**
	 * 错误接管
	 * 
	 * @param mixed $code
	 * @param mixed $error
	 * @param mixed $file
	 * @param mixed $line
	 * @return void
	 */
	public static function handleError($code, $error, $file = NULL, $line = NULL)
	{
		restore_error_handler();
		restore_exception_handler();
		
		if (error_reporting() & $code) {
			// This error is not suppressed by current error reporting settings
			// Convert the error into an ErrorException
			throw new ErrorException($error, $code, 0, $file, $line);
		}

		// Do not execute the PHP error handler
		return TRUE;
	}

	/**
	 * 捕捉那些不能被错误处理捕捉的错误，例如 E_PARSE
	 */
	public static function handleShutdown()
	{
		if ( ! self::$_init) {
			// Do not execute when not active
			return;
		}

		if (self::$errors AND $error = error_get_last() AND in_array($error['type'], self::$shutdownErrors)) {
			// Clean the output buffer
			ob_get_level() and ob_clean();

			// Fake an exception for nice debugging
			// self::handleException(new ErrorException($error['message'], $error['type'], 0, $error['file'], $error['line']));
			CException::handler(new ErrorException($error['message'], $error['type'], 0, $error['file'], $error['line']));

			// Shutdown now to avoid a "death loop"
			exit(1);
		}
	}
	
	private static $_coreClasses = array(
		'CFacade' => 'classes/CFacade.php',
		'CController' => 'classes/CController.php',
		'CModel' => 'classes/CModel.php',
		'CView' => 'classes/CView.php',
		'CHook' => 'classes/CHook.php',
		'CDbProxy' => 'classes/CDbProxy.php',
		'CRedisProxy' => 'classes/CRedisProxy.php',
		'CCrud' => 'classes/CCrud.php',
		'CCookie' => 'classes/CCookie.php',
		'CI18n' => 'classes/CI18n.php',
		'CUtils' => 'classes/CUtils.php',
		'CDebug' => 'classes/CDebug.php',
		'CException' => 'classes/CException.php',
		'CHttpException' => 'classes/CHttpException.php',
		'CTemplateController' => 'classes/CTemplateController.php',
		'CCache' => 'classes/CCache.php'
	);
}
spl_autoload_register(array('Yi','autoload'));
require(SYSPATH.DS.'interfaces'.EXT);


/**
 * 多语言处理
 *  __('Welcome back, :user', array(':user' => $username));
 * 
 * @param string $string 显示的文本
 * @param array $values 需要替换值的数组
 * @param string $lang 语言版本
 */
if ( ! function_exists('__')) {
	function __($string, array $values = NULL, $lang = 'zh-cn')
	{
		if ($lang !== Yi::$lang) {
			$string = CI18n::get($string);
		}
		return empty($values) ? $string : strtr($string, $values);
	}
}

/**
 * 抛出错误异常
 * 
 * @param string $msg 错误信息
 * @param boolean $throw 是否抛出异常
 */
if ( ! function_exists('throwException')) {
	function throwException($msg, $throw = TRUE )
	{
		if ($throw) {
			throw new ErrorException($msg);
		} else {
			echo $msg . "\n";
		}
	}
}

/**
 * 触发钩子
 * 
 * @param string $event 钩子的名称
 * @param array $args 参数数组
 */
if ( ! function_exists('triggerHook')) {
	function triggerHook($event, $args=NULL)
	{
		global $appHooks;  // app/config/conf.php中定义

		if ( !isset($appHooks[$event]) ) {
//			throwException(__('正在触发的钩子不存在') . ' - ' . $event);
			return FALSE;
		}
		
		$hooks = $appHooks[$event];
		if ( is_array($hooks) && count($hooks) > 0 ) {
			foreach ($hooks as $hook) {
				$ref = explode("::", $hook);
				
				if (count($ref) < 2) {
//					throwException(__('钩子注册格式不正确') . ' - ' . $event);
					continue;
				}
				
				$className = $ref[0];
				$class = CFacade::getInstance()->getMVC($className);
				$method = $ref[1];
				
				call_user_func_array(array($class, $method), $args);
			}
		}
		return TRUE;
	}
}


/**
 * 反射
 * 
 * @param string $called 需要调用的函数或方法，如："Ctrl_Map::enter"或者"triggerHook"
 * @param array $args 参数数组
 * @param object $context 上下文对象
 */
if ( ! function_exists('reflectCall')) {
	function reflectCall($called, $args=NULL, $context=NULL)
	{
		$return = '';
		
		$ref = explode("::", $called);
		
		if (count($ref) < 1 || count($ref) > 2) {
			return FALSE;
		}
		
		$className = $ref[0];
		if (count($ref) == 1) {
			$return = call_user_func_array($ref[0], $args);
		}
		if (count($ref) == 2) {
			if (get_class($context) == $className) {
				$class = $context;
			} else {
				$class = CFacade::getInstance()->getMVC($className, $args);
			}
		}
		$method = $ref[1];
		$return = call_user_func_array(array($class, $method), $args);
		
		return $return;
	}
}

/**
 * 返回当前的时间
 * 
 * @param string $format
 */
if ( ! function_exists('now')) {
	function now($format='Y-m-d h:i:s')
	{
		return date($format);
	}
}

/**
 * 获取对象的实例方法
 * $class_methods = get_class_methods('myclass');
 * or
 * $class_methods = get_class_methods(new myclass());
 * 
 * @param $className object || string
 */
if ( ! function_exists('getInstanceMethods')) {
	function getInstanceMethods($className) {
	    $returnArray = array();
	
	    foreach (get_class_methods($className) as $method) {
	        $reflect = new ReflectionMethod($className, $method);
	        if ( !$reflect->isStatic() && !$reflect->isConstructor() ) {
	            array_push($returnArray,$method);
	        }
	    }
	    return $returnArray;
	}
}

/**
 * Http响应
 * 
 * @param $status string
 */
if ( ! function_exists('httpResponse')) {
	function httpResponse($status='404')
	{
		ob_start();
		$view_file = SYSPATH . 'views' . DS . 'error' . $status . EXT;
		if ( is_file($view_file) ) {
			include $view_file;
		}
		echo ob_get_clean();
	}
}