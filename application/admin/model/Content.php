<?php 
namespace app\admin\model;
use think\Model;
use app\admin\model\ContentText;

class Content extends Model
{
    
    public static function getCont()
    {
        return Content::where(['del'=>0])->paginate(20);
    }
    
    //添加
    public static function setCont($data=array())
    {
        $data['create_time']=time();
        if(Content::strict(false)->insert($data))
        {
            $id=Content::getLastInsID();
            if(ContentText::insert(array('cid'=>$id,'text'=>$data['text'])))
            {
                return true;  
            }
            else
            {
                Content::delete($id);
                return false;
            }
            
        }
        else
        {
            return false;
        }
        
    }
    
    //获取单篇
    public static function getId($id)
    {
        $data=Content::where('id',$id)->find();
        $ContentText=ContentText::where('cid',$id)->find();
        $data['text']=$ContentText['text'];
        return $data;
    }
    
    //更新
    public static function updateCont($data=array())
    {
        Content::strict(false)->update($data);
        $data['cid']=$data['id'];
        ContentText::strict(false)->update($data);
        return 1;
    }

    //删除
    public static function delCont($id)
    {
        if(Content::where('id',$id)->update(array('del'=>1)))
        {
           return 1; 
        }
        else
        {
           return 0;
        }
        
    }

}

?>