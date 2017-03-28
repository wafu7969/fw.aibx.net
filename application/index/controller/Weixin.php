<?php
namespace app\index\controller;
use think\Controller;
use think\Db;

class Weixin extends Controller
{
    
    private $data = array();

    
	public function test()
    {

            //精确配比无数据 进行模糊配比
            //先读出所有的关键词 然后和用户输入的配比 配比率高的则输出
            $keyword=Db::name('keyword')->where(array('keytype'=>1,'del'=>0))->order('create_time desc')->select();
            
            $i=0;
            $reData=array();
            $strArr=$this->ch2arr('增值税发票案');
			
            foreach($keyword as $key=>$value)
            {
                
                $j=0;
                foreach($strArr as $key2=>$val2)
                {
                    //包含就加分1
                    if(!empty(stristr($value['keyword'],$val2)))
                    {
                        $j=$j+1;
                    }
                }
				
                if($j>$i)
                {
                    $i=$j;
                    $reData[$i]=$value;
                }
            }
            
        arsort($reData); 
        print_r($reData);
	}
	public function index()
    {
        
        //微信对接接口验证
        $request = request();
        //GET 是验证接口的
        //POST是接受微信发过来的XML消息
        
        if($request->method()=='GET')
        {
            $token=input('param.token');
            $timestamp=input('param.timestamp');
            $nonce=input('param.nonce');
            $signature=input('param.signature');
            $echostr=input('param.echostr');

            if(empty($token))
            {
               return "token error!";
            }
            
            $wx_bind=Db::name('wx_bind')->where('token',$token)->find();
            
            if(empty($wx_bind))
            {
               return "token error!"; 
            }
            $wxtoken=$wx_bind['wxtoken'];

            //验证请求连接的合法性
            $array = array($wxtoken, $timestamp, $nonce);
            sort($array, SORT_STRING);
            $str = implode($array);
            $str=sha1($str);
            
            if($signature==$str)
            {
                return $echostr;
            }
            else
            {
                return "error";
            }
        }
        //处理微信推送的各种XML消息
        else
        {
            
            $returnXML='';   //初始化返回的变量

            //转化微信发送过来的XML为数组
            $xml = file_get_contents("php://input");
            $xml = new \SimpleXMLElement($xml);
            $xml || exit();

            foreach ($xml as $key=>$value)
            {
                $this->data[$key]=strval($value);
            }
            
            //首先判断消息类型 其次判断事件类型
            if($this->data['MsgType']=='event') //接收微信的事件推送
            {
                //事件类型
                //Db::name('test')->insert(array('content'=>$this->data["Event"]));
                switch ($this->data["Event"])
                {
                    //关注时触发
                    case 'subscribe':
                        //关注自动回复
                        $wx_follow=Db::name('wx_follow')->limit(1)->find();
                        if(!empty($wx_follow['text']))
                        {
                            $this->data['text']=$wx_follow['text'];
                            $returnXML=$this->sendTextMsg($this->data);
                        }
                        
                        break;
                    //取消关注触发
                    case 'subscribe':
                        break;
                    //自定义菜单的点击事件
                    case 'CLICK':
                        //Db::name('test')->insert(array('content'=>$this->data["EventKey"]));
                        return $returnXML=$this->findKeyword($this->data["EventKey"]);
                        break;
                }
            }
            //微信的文本消息推送
            elseif($this->data['MsgType']=='text')
            {
                $content=$this->data['Content'];
                $returnXML=$this->findKeyword($content);
            }

            if(!empty($returnXML))
            {
                return $returnXML;
            }
            else
            {
                 return ;
            }
        }
    }


    //关键词对应的文本内容
    private function findKeyword($content)
    {
        //首先判断精确配比的
        $keyword=Db::name('keyword')->where(array("keyword"=>$content,'del'=>0))->order('create_time desc')->find();

        if(empty($keyword))
        {
            //精确配比无数据 进行模糊配比
            //先配比文本关键词 如果没有则使用问题检索
            $keyword=Db::name('keyword')->where(array('keytype'=>1,'type'=>1,'del'=>0,'keyword'=>array('like','%'.$content.'%')))->order('create_time desc')->find();
            
            if(empty($keyword))
            {
                $keyword=Db::name('keyword')->where(array('keytype'=>1,'type'=>2,'del'=>0))->order('create_time desc')->select();

                $i=0;
                $reData=array();
                $strArr=$this->ch2arr($content);
    			
                foreach($keyword as $key=>$value)
                {
                    
                    $j=0;
                    foreach($strArr as $key2=>$val2)
                    {
                        //包含就加分1
                        if(!empty(stristr($value['keyword'],$val2)))
                        {
                            $j=$j+1;
                        }
                    }
    				
                    if($j>=$i && $j!=0)
                    {
                        $i=$j;
                        $reData[]=$value;
                    }
                }
                
                if(empty($reData))
                {
                    $this->data['text']="";
                    $returnXML=$this->sendTextMsg($this->data);
                }
                else
                {
                    //$this->data['in_id']=$reData['in_id'];
                    //$this->data['type']=$reData['type'];
                    krsort($reData); 
                    $this->data['reData']=$reData;
                    $returnXML=$this->sendManyMsg($this->data);
                }
            }
            else
            {
                
                $text_reply=Db::name('text_reply')->where('id',$keyword['in_id'])->find();
                $this->data['text']=$text_reply['text'];
                $returnXML=$this->sendTextMsg($this->data);
            }
        }
        else
        {
            $this->data['in_id']=$keyword['in_id'];
            $this->data['type']=$keyword['type'];
            $returnXML=$this->sendMsg($this->data);
        }

        return $returnXML;
    }
	
    //文本发送函数
    private function sendTextMsg($data)
    {

        $toUser=$data["FromUserName"];
        $fromUser=$data["ToUserName"];
        $createTime=time();

        if(empty($data['text']))
        {
            $content='对不起，没有配比到相关内容!';
            return '<xml><ToUserName><![CDATA['.$toUser.']]></ToUserName><FromUserName><![CDATA['.$fromUser.']]></FromUserName><CreateTime>'.$createTime.'</CreateTime><MsgType><![CDATA[text]]></MsgType><Content><![CDATA['.$content.']]></Content></xml>';
        }
        else
        {
            $content=$data['text'];
            return '<xml><ToUserName><![CDATA['.$toUser.']]></ToUserName><FromUserName><![CDATA['.$fromUser.']]></FromUserName><CreateTime>'.$createTime.'</CreateTime><MsgType><![CDATA[text]]></MsgType><Content><![CDATA['.$content.']]></Content></xml>';
        }    
    }
    
    //多种类型发送函数 单条
    private function sendMsg($data)
    {
        //文本类型
        if($data['type']==1)
        {
            $toUser=$data["FromUserName"];
            $fromUser=$data["ToUserName"];
            $createTime=time();

            $text_reply=Db::name('text_reply')->where(array('del'=>0,'id'=>$data["in_id"]))->find();
            $data['text']=$text_reply['text'];

            if(empty($data['text']))
            {
                $content='对不起，没有配比到相关内容!';
                return '<xml><ToUserName><![CDATA['.$toUser.']]></ToUserName><FromUserName><![CDATA['.$fromUser.']]></FromUserName><CreateTime>'.$createTime.'</CreateTime><MsgType><![CDATA[text]]></MsgType><Content><![CDATA['.$content.']]></Content></xml>';
            }
            else
            {
                $content=$data['text'];
                return '<xml><ToUserName><![CDATA['.$toUser.']]></ToUserName><FromUserName><![CDATA['.$fromUser.']]></FromUserName><CreateTime>'.$createTime.'</CreateTime><MsgType><![CDATA[text]]></MsgType><Content><![CDATA['.$content.']]></Content></xml>';
            }   
        }
        //图文类型
        elseif($data['type']==2)
        {
            $toUser=$data["FromUserName"];
            $fromUser=$data["ToUserName"];
            $createTime=time();

            $img_text=Db::name('img_text')->where(array('del'=>0,'id'=>$data["in_id"]))->find();
            $data['title']=$img_text['title'];
            $data['info']=$img_text['info'];
            $data['pic']=$img_text['pic'];

            if(empty($data['title']))
            {
                $content='对不起，没有配比到相关内容!';
                return '<xml><ToUserName><![CDATA['.$toUser.']]></ToUserName><FromUserName><![CDATA['.$fromUser.']]></FromUserName><CreateTime>'.$createTime.'</CreateTime><MsgType><![CDATA[text]]></MsgType><Content><![CDATA['.$content.']]></Content></xml>';
            }
            else
            {
                $title=$data['title'];
                $info=$data['info'];
                $pic=$data['pic'];
                $url=config('domain').'/index.php/index/arcdetail/id/'.$img_text['id'].'.html';

                return '<xml><ToUserName><![CDATA['.$toUser.']]></ToUserName><FromUserName><![CDATA['.$fromUser.']]></FromUserName><CreateTime>'.$createTime.'</CreateTime><MsgType><![CDATA[news]]></MsgType><ArticleCount>1</ArticleCount><Articles><item><Title><![CDATA['.$title.']]></Title> <Description><![CDATA['.$info.']]></Description><PicUrl><![CDATA['.$pic.']]></PicUrl><Url><![CDATA['.$url.']]></Url></item></Articles></xml>';
            }   

        } 
    }

    //多图文发送 主要用于问题检索发送的内容
    public function sendManyMsg($data)
    {
        $toUser=$data["FromUserName"];
        $fromUser=$data["ToUserName"];
        $createTime=time();

        $arcNum=count($data['reData']);
        $arcNum=$arcNum>=7?7:$arcNum;
        $returnStr='<xml><ToUserName><![CDATA['.$toUser.']]></ToUserName><FromUserName><![CDATA['.$fromUser.']]></FromUserName><CreateTime>'.$createTime.'</CreateTime><MsgType><![CDATA[news]]></MsgType><ArticleCount>'.$arcNum.'</ArticleCount><Articles>';

        $fnum=1;
        foreach($data['reData'] as $k=>$v)
        {
            if($fnum<=7)
            {
                $img_text=Db::name('img_text')->where(array('del'=>0,'id'=>$v["in_id"]))->find();

                if(!empty($img_text))
                {
                    $title=$img_text['title'];
                    $info=$img_text['info'];
                    $pic=$img_text['pic'];
                    $url=config('domain').'/index.php/index/arcdetail/id/'.$img_text['id'].'.html';

                    $returnStr=$returnStr.'<item><Title><![CDATA['.$title.']]></Title> <Description><![CDATA['.$info.']]></Description><PicUrl><![CDATA['.$pic.']]></PicUrl><Url><![CDATA['.$url.']]></Url></item>'; 
                }
                
            }
            else
            {
                break;
            }
            

            $fnum=$fnum+1;
        }
        
        $returnStr=$returnStr.'</Articles></xml>';
        //Db::name('test')->insert(array('content'=>$returnStr));
        return $returnStr;
        
    }


    
    //中文字符串转为数组
    private function ch2arr($str)
    {
        $length = mb_strlen($str, 'utf-8');
        $array = array();
        for ($i=0; $i<$length; $i++)  
            $array[] = mb_substr($str, $i, 1, 'utf-8');    
        return $array;
     }
     
	 //排序
	 public function bubble($arr)
	 {
		
	 }
}