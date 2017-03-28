<?php
namespace app\admin\controller;
use think\Db;
use app\admin\model\Type;

class FwType extends Admin
{ 
    public function index()
    {  
        $reid=input('param.reid');
        if(empty($reid))
        {
            $reid=0;
        }
        $typeArr=Type::getType($reid);
        $this->assign('reid',$reid);
        $this->assign('typeArr',$typeArr);
        return $this->fetch();
    }

    //添加栏目
    public function add()
    {  

        $request = request();
        if($request->method()=='POST')
        {
            $data=input('param.');
            
            if(empty($data['name']))
            {
                $this->error('请输入栏目的名称');
            }

            //上传图片
            $file = $request->file('pic');
            if(!empty($file))
            {
               $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads');
               $data['pic']=str_replace('\\','/','/uploads/'.$info->getSaveName()); 
            }
            else
            {
               $data['pic']="";
            }
            $nameStr=str_replace('，',',',$data['name']);
            $nameArr=explode(',',$nameStr);
            foreach ($nameArr as $key => $value)
            {
               $data['name']=$value;
               Type::addType($data);
            }

            return $this->success('添加成功！',url('index'));
        }
        else
        {
            $reid=input('param.reid');
            if(empty($reid))
            {
                $reid=0;
            }
            $this->assign('reid',$reid);
            return $this->fetch();
        }
    }

    //修改栏目
    public function alter()
    {
        $request=request();
        if($request->method()=='POST')
        {
            $data=input('param.');
            
            if(empty($data['name']))
            {
                $this->error('请输入栏目的名称');
            }

            //上传图片
            $file = $request->file('pic');
            if(!empty($file))
            {
               $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads');
               $data['pic']=str_replace('\\','/','/uploads/'.$info->getSaveName()); 
            }

            Type::update($data);
            return $this->success('修改成功！',url('index'));
        }
        else
        {
            $id=input('param.id');
            $type=Type::get($id);
            $this->assign('type',$type);
            return $this->fetch();
        }
        
    }

    //删除栏目
    public function del()
    {
        $id=input('param.id');
        $type=Type::getType($id);
        if($type)
        {
            return array('code'=>2);
        }
        else
        {
            Type::destroy($id);
            return array('code'=>1);
        }
    }
  
}