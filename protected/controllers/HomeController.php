<?php defined('SYSPATH') or die('No direct script access.');
/**
 * 游戏首页
 * 包括登陆检查等
 * 
 * @author Andy Cai <huayicai@gmail.com>
 */
class HomeController extends CTemplateController
{
	public $template = 'home';
	
	public function __construct()
	{
		parent::__construct();
	}
	
	public function actionLogin()
	{
		// 验证参数
// 		if( !Lib_AppUtil::check_array( Lib_Global::$param, array( 'openid', 'openkey' ) ) )
// 			return $this->view->rsp_err( __('缺少参数') );
// 		
// 		$userCtrl = $this->get_ctrl('user');
// 		$userInfo = $userCtrl->get_user_info( Lib_Global::$param['openid'], Lib_Global::$param['openkey'] );
		$userInfo = array('name'=>'Yitong net', 'year'=>2011);
		
		$this->template->set( array('title'=>__('亦同游社交游戏吧'), 'content'=>$userInfo, 'copyright'=>__('亦同网络')) );
		
	}
}
