<?php defined('SYSPATH') or die('No direct script access.');

/////////// 必读！！！！！！！！！/////////////
/// 1. 本文件只保存部署相关的静态配置       ///
/// 2. 各服配置可能不同，严禁全服同步该文件 ///
/// 3. 不要在该文件写任何逻辑相关的代码     ///
/// 4. 添加新的配置要知会所有开发人员       ///
///////////////////////////////////////////////

return array
(
	'settings' => array(
		'errors' => TRUE,
		'theme' => 'puremvc'
	),

	// 数据库连接
	'db'  => array(
		'host' => '192.168.1.93',
		'port' => 3306,
		'user' => 'yitong',
		'password' => '123456',
		'dbname' => 'game'
	),
	
	// 缓存连接
	'redis'  => array(
		'host' => '192.168.1.93',
		'port' => 6379
	),
	
	'redisConf'  => array(
		'host' => '192.168.1.93',
		'port' => 6380
	),
	
	// 文件操作目录
	'dataPath' => 'E:/projects/pttw/server/trunk/script/php/data/',
	'dataConfPath' => 'conf/',
	'dataLogPath' => 'log/',
	'dataLockPath' => 'lock/',
	
	// 认证码
	'authKey' => 'E@#Wisd7&%^2lpw23',
	
	// 钩子
	'hooks'=>require(dirname(__FILE__).'/hooks.php'),
);