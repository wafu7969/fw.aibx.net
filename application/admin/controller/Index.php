<?php
namespace app\admin\controller;
use think\Controller;
use think\Db;
use think\Session;
use app\admin\model\Admin;

class Index extends Controller
{
    public function index()
    {
        return $this->fetch();
    }

    public function login()
    {
        $userName=input('post.userName');
        $passWord=input('post.passWord');

        if(empty($userName) || empty($passWord))
        {
            return $this->error('用户名或密码不能为空');
        }
        else
        {
            $admin=new Admin;
            $result=$admin->login($userName,$passWord);

            if($result==0)
            {
               return $this->error($admin->getError()); 
            }
            else
            {
               Session::set('adminid',$result);
               Session::set('username',$userName);
               return $this->success('登陆成功,跳转到管理主页',url('Main/index')); 
            }
            
        }
    }
}
