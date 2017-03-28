<?php
namespace app\admin\controller;
use think\Db;
use think\Session;
use app\admin\model\Node;

class Main extends Admin
{
    public function index()
    {
        //初始化右侧菜单
        $node=new Node;
        $navs=$node->getNode();
        $this->assign('navs',$navs);
        foreach($navs as $k=>$v){
            $pid[] = $v['id'];
        }
        $navx=$node->getNodePid($pid);
        $this->assign('navx',$navx);
        return $this->fetch();
    }

    public function mainIndex()
    {
        //文章和订单统计
        $cont_voiceNum=Db::name('cont_voice')->count();
        $img_textNum=Db::name('img_text')->count();
        $arcCount=$cont_voiceNum+$img_textNum;
        $ordersNum=Db::name('orders')->where(array('nstatus'=>1))->count();  //'status'=>1,
        
        //获得财务统计
        $Finance=new Finance();
        $today=$Finance->getToday();
        $month=$Finance->getMonth();
        $total=$Finance->getTotal();

        //最新订单记录前5条
        $orders=Db::name('orders')->where(array('nstatus'=>1))->order('id desc')->limit(5)->select(); //'status'=>1,

        foreach($orders as $key=>$val)
        {
           //查找用户信息
           $user_info=Db::name('user_info')->where('id',$val['user_id'])->find();
           $portrait=get_wxheadimg($user_info['portrait'],'46');
           $orders[$key]['portrait']=$portrait;


           $orders[$key]['wechaname']=$user_info['wechaname'];
           //查找商品信息
           $cont_curriculum=Db::name('cont_curriculum')->where('id',$val['curr_id'])->find();
           $orders[$key]['title']=$cont_curriculum['title'];
        }

        //最新评论 前10条
        $cont_comment=Db::name('cont_comment')->where('del',0)->order('id desc')->limit(10)->select();
        
        foreach($cont_comment as $k=>$v)
        {
             $user_info=Db::name('user_info')->where('id',$v['user_id'])->find();
             $portrait=get_wxheadimg($user_info['portrait'],'64');
             $cont_comment[$k]['portrait']=$portrait;
        }
        
        $this->assign('orders',$orders);
        $this->assign('cont_comment',$cont_comment);
        $this->assign('arcCount',$arcCount);
        $this->assign('ordersNum',$ordersNum);
        $this->assign('today', $today);
        $this->assign('month', $month);
        $this->assign('total', $total);
        return $this->fetch();
    }
    
    //修改密码
    public function alterps()
    {
        $request=request();
        if($request->method()=='POST')
        {
           $data=input('post.');
           if(empty($data['oldps']) || empty($data['newps']) || empty($data['newps2']))
           {
               $this->error('新旧密码都不能为空！');
           }
           if($data['newps']!=$data['newps2'])
           {
               $this->error('新密码和确认密码不一致！');
           }

           $admin=Db::name('admin')->where('id',1)->find(); 
           if($admin['password']!=md5($data['oldps']))
           {
               $this->error('旧密码不正确！');
           }
           else
           {
              Db::name('admin')->where('id',1)->update(array('password'=>md5($data['newps']))); 
              $this->error('密码修改成功！');
           }
        }
        else
        {

        }
        return $this->fetch();
    }
    
    //退出
    public function out()
    {
        Session::clear();
        $this->redirect('/admin.php');
    }
}
