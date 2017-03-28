<?php 
namespace app\admin\model;
use think\Model;

class Node extends Model
{
    public function getNode()
    {
        $where['display']=1;
        $where['status']=1;
        $where['level']=0;   //应用
        $order['sort']='asc';
        $navs=$this->where($where)->order($order)->select();
        return $navs;
    }

    public function getNodePid($pid=array())
    {
       $order['sort']='asc';
       $wheres['display']=1;
       $wheres['status']=1;
       $wheres['level']=2;   //模块
       return $navx=$this->where($wheres)->where('pid','in',$pid)->order($order)->select();
    }
}

 ?>