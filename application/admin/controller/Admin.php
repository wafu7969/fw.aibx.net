<?php
namespace app\admin\controller;
use think\Controller;
use think\Db;
use think\Session;

class Admin extends Controller
{
    //判断session是否已经过期 过期自动登录
	protected function _initialize()
	{
		$adminid=Session::get('adminid');
		$username=Session::get('username');
		if(empty($adminid) || empty($username))
		{
			$this->redirect('/admin.php');
		}
	}
}
