<?php
namespace app\index\controller;
use think\Controller;
use apiOauth\ApiOauth;
use think\Db;
use think\Session;

class Wap extends Controller
{
    public $fansInfo;
	//网页授权函数
	public function getUserInfo()
	{
		
		if(empty(Session::has('openid')))
		{
		
			$wx_bind=Db::name('wx_bind')->find();
	
			$info['appid']=$wx_bind['appid'];
			$info['appsecret']=$wx_bind['appsecret'];
			
			$ApiOauth=new ApiOauth();
			$ApiOauthArr=$ApiOauth->webOauth($info,'snsapi_userinfo');
			//获取用户信息
			$getUserInfo=$ApiOauth->get_fans_info($ApiOauthArr['access_token'],$ApiOauthArr['openid']);
			$user_info=Db::name('user_info')->where('wecha_id',$getUserInfo['openid'])->find();
			if(empty($user_info))
			{
				$data['wechaname']=$getUserInfo['nickname'];
				$data['wecha_id']=$getUserInfo['openid'];
				$data['sex']=$getUserInfo['sex']?'男':'女';
				$data['portrait']=$getUserInfo['headimgurl'];
				$data['city']=$getUserInfo['province'].$getUserInfo['city'];
				$data['regtime']=time();
				Db::name('user_info')->insert($data);
			}
			else
			{
				$data['wechaname']=$getUserInfo['nickname'];
				$data['sex']=$getUserInfo['sex']?'男':'女';
				$data['portrait']=$getUserInfo['headimgurl'];
				$data['city']=$getUserInfo['province'].$getUserInfo['city'];
				Db::name('user_info')->where('wecha_id',$getUserInfo['openid'])->update($data);
			}
			
			Session::set('openid',$getUserInfo['openid']);
		}

		//获得用户的信息
		$user_info=Db::name('user_info')->where('wecha_id',Session::get('openid'))->find();
		$this->fansInfo['id']=$user_info['id'];
		$this->fansInfo['wechaname']=$user_info['wechaname'];
		$this->fansInfo['wecha_id']=$user_info['wecha_id'];
		$this->fansInfo['portrait']=$portrait=$user_info['portrait'];
		$portrait = substr($portrait,0,strlen($portrait)-1);
		
		$this->fansInfo['portrait64']=$portrait.'64';
		$this->fansInfo['portrait96']=$portrait.'96';
		$this->fansInfo['portrait132']=$portrait.'132';

		$this->fansInfo['sex']=$user_info['sex'];
		$this->fansInfo['vip']=$user_info['vip'];
		$this->fansInfo['truename']=$user_info['truename'];
		$this->fansInfo['city']=$user_info['city'];
		$this->fansInfo['tel']=$user_info['tel'];
		$this->fansInfo['birthday']=$user_info['birthday'];
        $this->fansInfo['company']=$user_info['company'];
        $this->fansInfo['position']=$user_info['position'];
        $this->fansInfo['note_time']=$user_info['note_time'];
	}
	
}