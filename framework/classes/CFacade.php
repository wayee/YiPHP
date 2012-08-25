<?php defined('SYSPATH') or die('No direct script access.');

/**
 * 框架的总控制器，单态模式
 * 
 * @author Andy Cai (huayicai@gmail.com)
 * 
 */
class CFacade
{
	protected static $_instance;
	protected $_hashMap = array();
	protected $_route;
	protected $_viewClass;
	protected $_config;
	protected $_body;
	protected $_action='Index';
	protected $_controller='Index';
	
	protected function __construct()
	{
	}
	
	public static function getInstance()
	{
		if (self::$_instance == NULL) self::$_instance = new CFacade();
		return self::$_instance;
	}
	
	/**
	 * web应用程序的入口，接口预留，重构后使用
	 * - web应用程序初始化
	 * - 路由
	 * - 控制器action执行
	 * - 试图内容输出
	 */
	public function runWebApp($config)
	{
		// 加载配置文件
		$this->_config = require($config);
		
		$settings = $this->_config['settings'];
		
		// 初始化应用
		Yi::init($settings);
		
		// 加入路由
		//$this->_route->set();
		
		// 响应输出
		$this->request();
	}
	
	/**
	 * Gets or sets 页面响应内容
	 * 
	 * @param string $content 内容
	 * @return mixed
	 */
	public function response($content = NULL)
	{
		if ($content === NULL) {
			return $this->_body;
		}
		
		$this->_body = (string) $content;
		return $this;
	}
	
	/**
	 * Gets or sets 控制器
	 * 
	 * @param string $controller 控制器名称
	 * @return mixed
	 */
	public function controller($controller = NULL)
	{
		if ($controller === NULL) {
			return $this->_controller;
		}
		
		$this->_controller = (string) $controller;
		return $this;
	}
	
	/**
	 * Gets or sets 控制器方法Action
	 * 
	 * @param string $action 方法名称
	 * @return mixed
	 */
	public function action($action = NULL)
	{
		if ($action === NULL) {
			return $this->_action;
		}
		
		$this->_action = (string) $action;
		return $this;
	}
	
	/**
	 * 加载配置文件
	 */
	public function loadConfig()
	{
		if (is_string($this->_config)) {
			Yi::$config = include($this->_config);
		}
	}
	
	public function getView($className=NUll, $args=NULL)
	{
		if (empty($className)) {
			$className = $this->_viewClass;
		} else {
			Yi::import($className);
		}
		return $this->_getObject($className, $args);
	}

	/**
	 * 缓存控制器实例
	 */
	public function cacheController($obj)
	{
		$className = get_class($obj);
		$this->_hashMap[$className] = $obj;
	}
	
	/**
	 * 获取控制器实例
	 * 
	 * @param string $className 控制器名称
	 * @param array $args 参数数组
	 * @return object
	 */
	public function getController($className, $args=NULL)
	{
		if (empty($className)) return NULL;
		Yi::importMvc($className, 'controllers'.DS.$className.'Controller'.EXT);
		return $this->_getObject($className.'Controller', $args);
	}
	
	/**
	 * 获取模型实例
	 * 
	 * @param string $className 模型名称
	 * @param array $args 参数数组
	 * @return object
	 */
	public function getModel($className, $args=NULL)
	{
		if (empty($className)) return NULL;
		Yi::importMvc($className, 'models'.DS.$className.'Model'.EXT);
		return $this->_getObject($className.'Model', $args);
	}
	
	/**
	 * 获取VO实例
	 * 
	 * @param string $className VO名称
	 * @param array $args 参数数组
	 * @return object
	 */
	public function getVo($className, $args=NULL)
	{
		if (empty($className)) return NULL;
		Yi::importMvc($className, 'models'.DS.'vo'.DS.$className.'Vo'.EXT);
		$vo = $this->_getObject($className.'Vo', $args, true);
		$vo->init();
		return $vo; 
	}
	
	
	/**
	 * 私有方法，路过请绕道
	 */
	
	
	/**
	 * 获取对象实例
	 * <br> Controller
	 * <br> Model
	 * <br> View
	 * <br> Vo
	 * 
	 * @param mixed $className 类名
	 * @param mixed $args 类的构造函数参数
	 * @param bool $cached 是否从缓存中取
	 * @return 对象实例
	 */
	private function _getObject($className, $args=NULL, $cached=TRUE)
	{
		if( !$cached ) {
			$object = new $className($args);
			return $object;
		}

		if(is_array($this->_hashMap) && key_exists($className, $this->_hashMap))
			return $this->_hashMap[$className];
		
		$object = NULL;
		if (empty($args)) {
	      $object = new $className();                                                                                                                                                          
		} else {
	      $ref = new ReflectionClass($className);
	      $object = $ref->newInstanceArgs($args); 	// php > 5.1.3
	    }
		$this->_hashMap[$className] = $object;
		return $object;
	}
	
	/**
	 * 请求解析
	 * <br> 处理URL中的请求
	 * <br> 路由到具体的Controller的具体方法(Action)
	 */
	private function request()
	{
		$this->_controller = 'welcome';
		if (isset($_GET['c'])) {
			$this->_controller = (string) $_GET['c'];
		}

		$this->_action = 'index';
		if (isset($_GET['m'])) {
			$this->_action = (string) $_GET['m'];
		}
		
		if ( !is_file(APPPATH.DS.'controllers'.DS.$this->_controller.'Controller'.EXT)) {
			throw new CHttpException('404', 'No page found');
			// Yi::httpResponse('404');
			return;
		}
		
		Yi::importMvc($this->_controller.'Controller', 'controllers'.DS.ucfirst($this->_controller).'Controller'.EXT);
		$class = new ReflectionClass($this->_controller.'Controller');
		$this->_controller = $class->newInstance();
		
		if ( !method_exists($this->_controller, 'action'.$this->_action)) {
			throw new CHttpException('404', 'No page found');
			return;
		}
		
		$this->cacheController($this->_controller);
		
		if (isset($_GET['viewclass'])) {
			$this->_viewClass = (string) $_GET['viewclass'];
		}
		
		// 执行调用
		$class->getMethod('before')->invoke($this->_controller);
		triggerHook('BEFORE_EXECUTE_ACTION');
		$class->getMethod('action'.$this->_action)->invoke($this->_controller);
		triggerHook('AFTER_EXECUTE_ACTION');
		$class->getMethod('after')->invoke($this->_controller);
		
		echo $this->_body;
		
	}
} // End CFacade