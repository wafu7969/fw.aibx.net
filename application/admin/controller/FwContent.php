<?php
namespace app\admin\controller;
use app\admin\model\Content;

class FwContent extends Admin
{ 
    public function index()
    {  
        $contList=Content::getCont();
        $this->assign('contList',$contList);
        return $this->fetch();
    }

    //添加范文
    public function add()
    {  
       $request=request();
       if($request->method()=='POST')
       {
            $data=input('param.');
            $file = $request->file('pic');
            if(!empty($file))
            {
               $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads');
               $data['pic']=str_replace('\\','/','/uploads/'.$info->getSaveName()); 
            }

            Content::setCont($data) ? $this->success('添加成功！',url('index')) : $this->error('添加失败');
       }
       else
       {
            return $this->fetch(); 
       }
        
    }

    //修改范文
    public function alter()
    {
       $id=input('param.id');
       $request=request();
       if($request->method()=='POST')
       {
            $data=input('param.');
            $file = $request->file('pic');
            if(!empty($file))
            {
               $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads');
               $data['pic']=str_replace('\\','/','/uploads/'.$info->getSaveName()); 
            }
            
            Content::updateCont($data) ? $this->success('修改成功！',url('index')) : $this->error('修改失败');
       }
       else
       {
            $content=Content::getId($id);
            $this->assign('content',$content);
            return $this->fetch(); 
       }
       
    }

    //删除范文
    public function del()
    {
        $id=input('param.id');
        Content::delCont($id) ? $return=array('code'=>1) : $return=array('code'=>0);
        return $return;
    }
  
}