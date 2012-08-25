<?php defined('SYSPATH') or die('No direct script access.');

/**
 * interface 接口集中营
 * 
 * @author Andy Cai (huayicai@gmail.com)
 * 
 */

interface IController
{
	public function before();
	public function after();
}

interface IView
{
	public function render();
}

interface IModel
{
	public function db();
}
