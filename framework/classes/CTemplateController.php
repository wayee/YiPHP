<?php defined('SYSPATH') or die('No direct script access.');

/**
 * 自动模板控制器基类
 * 
 * @author Andy Cai (huayicai@gmail.com)
 * 
 */
class CTemplateController extends CController
{
	protected $facade;
	protected $view;
	
	public $template = 'tempalte';
	public $autoRender = TRUE;

	public function before()
	{
		parent::before();
		
		if ($this->autoRender === TRUE) {
			$this->template = CView::factory($this->template);
		}
	}

	public function after()
	{
		if ($this->autoRender === TRUE)
		{
			$this->facade->response($this->template->render());
		}

		parent::after();
	}
} // End CTemplateController