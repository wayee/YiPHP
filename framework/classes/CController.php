<?php defined('SYSPATH') or die('No direct script access.');

/**
 * 控制器基类
 * 
 * @author Andy Cai (huayicai@gmail.com)
 * 
 */
class CController implements IController
{
	protected $facade;

	public function __construct()
	{
		$this->facade = CFacade::getInstance();
	}
	
	public function before()
	{
		// Nothing by default
	}

	public function after()
	{
		// Nothing by default
	}
} // End CController