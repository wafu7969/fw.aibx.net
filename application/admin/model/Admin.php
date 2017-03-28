<?php 
namespace app\admin\model;
use think\Model;

class Admin extends Model
{
    
    public function login($userName,$passWord)
    {
       $passWord=md5($passWord);
       $admin=$this->field('id,username')->where(['username'=>$userName,'password'=>$passWord])->find();
       if($admin)
       {
          $admin->save(['login_time'=>time()]);
          return $admin->id;
       }
       else
       {
          $this->error='用户名或者密码错误！';
          return 0;
       }
    }
}

 ?>