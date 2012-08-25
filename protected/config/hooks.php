<?php defined('SYSPATH') or die('No direct script access.');

/**
 * 钩子注册
 * 使用::分割类名和方法名，如：'Ctrl_Home::hello'
 */

// 系统
 $appHooks['BEFORE_EXECUTE_ACTION'][] = '';
 $appHooks['AFTER_EXECUTE_ACTION'][] = '';

// 角色
 $appHooks['BEFORE_ROLE_ONLINE'][] = '';
 $appHooks['AFTER_ROLE_ONLINE'][] = 'Ctrl_Map::_online_and_enter';
 $appHooks['BEFORE_ROLE_OFFLINE'][] = '';
 $appHooks['AFTER_ROLE_OFFLINE'][] = 'Ctrl_Map::_broadcast_leaving';
 $appHooks['BEFORE_ROLE_UPGRADE'][] = '';
 
 // 角色升级
 $appHooks['AFTER_ROLE_UPGRADE'][] = 'Ctrl_Army::_hook_update_role_army';
 
 // 角色创建完成
 $appHooks['AFTER_ROLE_CREATE'][] = 'Ctrl_Army::_hook_init_role_army';
 $appHooks['AFTER_ROLE_CREATE'][] = 'Ctrl_Homeland::_hook_create_homeland';
 $appHooks['AFTER_ROLE_CREATE'][] = 'Ctrl_Army::_hook_init_homeland_army';

 // 创建我的家园
 $appHooks['BEFORE_CREATE_HOMELAND'][] = 'Ctrl_Map::_hook_create_homeland_map';
 $appHooks['AFTER_CREATE_HOMELAND'][] = '';
 
 // 地图
 $appHooks['BEFORE_ENTER_MAP'][] = '';
 $appHooks['AFTER_ENTER_MAP'][] = '';
 $appHooks['BEFORE_LEAVE_MAP'][] = ''; 
 $appHooks['AFTER_LEAVE_MAP'][] = ''; 
 
 return $appHooks;