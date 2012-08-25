<?php defined('SYSPATH') or die('No direct script access.');
/**
 * 游戏首页
 * 包括登陆检查等
 * 
 * @author Andy Cai <huayicai@gmail.com>
 */
class WelcomeController extends CTemplateController
{
	public $template = 'home';
	
	public function __construct()
	{
		parent::__construct();
	}
	
	public function actionLogin()
	{
		$userInfo = array('name'=>'Yitong net', 'year'=>2011);
		
		$this->template->set( array('title'=>__('亦同游社交游戏吧'), 'content'=>$userInfo, 'copyright'=>__('亦同网络')) );
		
	}
}
