<?php 
namespace app\admin\model;
use think\Model;

class Type extends Model
{
    // 开启时间字段自动写入
    protected $autoWriteTimestamp = true; 

    public static function getType($reid=0)
    {
       return Type::where('reid',$reid)->order('sort desc')->select();
    }

    public static function addType($data)
    {
       $data['create_time']=time();
       return Type::insert($data);
    }

    //添加时间读取器
    protected function getCreateTimeAttr($value)
    {
        return date('Y-m-d',$value);
    }
    //是否显示读取器
    protected function getIsshowAttr($value)
    {
        return $value ?'显示':'隐藏';
    }
}


 ?>