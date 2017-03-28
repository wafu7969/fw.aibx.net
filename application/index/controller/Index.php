<?php
namespace app\index\controller;
use think\Controller;
use think\Db;
use think\Session;
use think\Config;
use apiOauth\ApiOauth;
use wxJsPay\WxJsPay;

class Index extends Wap
{
    protected $timeStamp;
    protected $nonceStr;
    protected $signature;
    protected $shareTitle;
    protected $shareDesc;
    protected $shareLink;
    protected $shareImgUrl;

    protected function _initialize()
    {
        //生成默认的分享内容
        $this->shareTitle=Config::get('sitename');      // 分享标题
        $this->shareDesc='';       // 分享描述
        $this->shareLink=Config::get('domain').url('index');       // 分享链接
        $this->shareImgUrl=Config::get('domain').HTML_STATIC.'/images/shareLogo.jpg';     // 分享图标

        //授权
        $this->getUserInfo();
        //获得未读小纸条的个数
        $nodeNum=Db::name('send_infor')->where("create_time > ".$this->fansInfo['note_time']." and (send_uid =".$this->fansInfo['id']." or send_uid=0)")->count();
        $this->assign('nodeNum',$nodeNum);
        //获得当前的控制器名
        $request=request();
        $action_name=$request->instance()->action();
        $this->assign('action_name',$action_name);
        //微信jdk 验证
        $apiOauth=new apiOauth();
        $ticket = $apiOauth->getJsApiTicket(Config::get('Appid'),Config::get('Appsecret'));

        $this->timeStamp = time();
        $this->nonceStr  = rand(100000,999999);
        $url = "http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
        //获得jsdk签名
        $this->signature = $apiOauth->getSignature($this->nonceStr,$ticket,$this->timeStamp,$url);

        $this->assign('timeStamp',$this->timeStamp);
        $this->assign('nonceStr',$this->nonceStr);
        $this->assign('signature',$this->signature);
        $this->assign('fansInfo',$this->fansInfo);

        $this->assign('shareTitle',$this->shareTitle);
        $this->assign('shareDesc',$this->shareDesc);
        $this->assign('shareLink',$this->shareLink);
        $this->assign('shareImgUrl',$this->shareImgUrl);
    }
	
    public function index()
    {
		//读取首页广告  要求读取有效范围内的广告
        $where['del']=0;
        $where['status']=1;

		$cont_ad=Db::name('cont_ad')->where($where)->select();
        
        foreach($cont_ad as $k=>$v)
        {
            
            if($v['start_time']<=time())
            {
                if($v['end_time']==0)
                {
                    $returnAd[]=$v;
                }
                elseif($v['end_time']>time())
                {
                    $returnAd[]=$v;
                }
            }
        }
        
        $adNum=count($returnAd);
        if($adNum>0)
        {
           //获得第一张广告图
           $returnAdOne=$returnAd[0];
           //获得最后一张广告图
           $returnAdLast=$returnAd[$adNum-1];
        }
        
        $cont_curriculum=$this->getCurriculumList();

		$this->assign('cont_curriculum',$cont_curriculum);
        $this->assign('returnAdOne',$returnAdOne);
        $this->assign('returnAdLast',$returnAdLast);
        $this->assign('returnAd',$returnAd);
        $this->assign('cont_curriculumNum',count($cont_curriculum));
		return $this->fetch();
    }

    public function getCurriculumList($start=0)
    {
        $offsize=Config::get('page');
        //读取语音列表
        $cont_curriculum=Db::name('cont_curriculum')->where(array('status'=>1,'del'=>0))->order('id desc')->limit($start,$offsize)->select();
        //获得评论数
        foreach($cont_curriculum as $k=>$v)
        {
             $cont_comment=Db::name('cont_comment')->where(array('voice_id'=>$v['id'],'del'=>0))->count();
             $cont_curriculum[$k]['commentNum']=$cont_comment;
        }
        return $cont_curriculum;

    }
	
    public function ajaxGetCurriculum()
    {
        $offsize=input('param.offsize');
        $cont_curriculum=$this->getCurriculumList($offsize);
        $returnStr='';
        foreach($cont_curriculum as $k=>$v)
        {
            $returnStr=$returnStr.'<div class="inlist" onClick="window.location.href=\''.url('voiceDetail',array('id'=>$v['id'])).'\'">
                    <div class="yyimg"><img src="'.$v['pic'].'" /></div>
                    <div class="yyinfo">
                          <div class="yytitle">'.$v['title'].'</div>
                          <div class="yyit">
                                <div class="yyitleft">
                                      <div class="mui-icon mui-icon-chat yypl"> '.$v['click'].'w</div>
                                      <div class="mui-icon mui-icon-chat yypl"> '.$v['commentNum'].'</div>
                                </div>
                                <div class="yyitright">'.date('Y-m-d',$v['create_time']).'</div>
                          </div>
                    </div>
              </div>';
        }

        return $returnStr;
    }
    
    //课程的首页
    public function currIndex()
    {
        $id=input('param.id');
        $cont_curriculum=Db::name('cont_curriculum')->where(array('id'=>$id,'status'=>1,'del'=>0))->find();
        
        if(!empty($cont_curriculum))
        {
            $orders=0;
            $voiceList=$this->getVoiceList(0,$id);
            //判断当前用户是否购买了该课程
            if($this->isBuy($id))
            {
                $orders=1;
            }
            $this->assign('cont_curriculum',$cont_curriculum);
            $this->assign('voiceList',$voiceList);
            $this->assign('voiceListNum',count($voiceList));
            $this->assign('orders',$orders);
            return $this->fetch();
        }
        else
        {
            $this->error('你所查找的课程不错在！');
        }

    }

    public function ajaxGetVoice()
    {
        $currId=input('param.currId');
        $offsize=input('param.offsize');
        $cont_voice=$this->getVoiceList($offsize,$currId);
        $returnStr='';
        foreach($cont_voice as $k=>$v)
        {
            $returnStr=$returnStr.'<div class="inlist" onClick="window.location.href=\''.url('voiceDetail',array('id'=>$v['id'])).'\'">
                    <div class="yyimg"><img src="'.$v['pic'].'" /></div>
                    <div class="yyinfo">
                          <div class="yytitle">'.$v['title'].'</div>
                          <div class="yyit">
                                <div class="yyitleft">
                                      <div class="mui-icon mui-icon-chat yypl"> '.$v['click'].'w</div>
                                      <div class="mui-icon mui-icon-chat yypl"> '.$v['commentNum'].'</div>
                                </div>
                                <div class="yyitright">'.date('Y-m-d',$v['create_time']).'</div>
                          </div>
                    </div>
              </div>';
        }

        return $returnStr;
    }
    
    public function getVoiceList($start=0,$currId)
    {
        $offsize=Config::get('page');
        //读取语音列表
        $cont_voice=Db::name('cont_voice')->where(array('status'=>1,'del'=>0,'curr_id'=>$currId))->order('sort desc,id asc')->limit($start,$offsize)->select();
        //获得评论数
        foreach($cont_voice as $k=>$v)
        {
             $cont_comment=Db::name('cont_comment')->where(array('voice_id'=>$v['id'],'del'=>0))->count();
             $cont_voice[$k]['commentNum']=$cont_comment;
        }
        return $cont_voice;

    }


    //音频详细页
   public function voiceDetail()
   {
	    
        $id=input('param.id');
        $from=input('param.from');  //记录分享的来源 用于判断分享几条后不能再播放语音
        $pfStatus=0;
        if(!empty($from))
        {
			
            $share_source=Db::name('share_source')->where(array('wecha_id'=>$this->fansInfo['wecha_id'],'from'=>$from,'voice_id'=>$id))->find();
	if(empty($share_source))
	{
	      $share_source=Db::name('share_source')->where(array('from'=>$from))->count();
                   if($share_source<Config::get('shareNum'))
                   {
		Db::name('share_source')->insert(array('wecha_id'=>$this->fansInfo['wecha_id'],'from'=>$from,'voice_id'=>$id));
		$pfStatus=1;
	      }
			   
            }
	else
	{
	      $pfStatus=1;
	}
        }
        

        $order_id="";
        $cont_voice=Db::name('cont_voice')->where('id',$id)->find();
        Db::name('cont_voice')->where('id',$id)->setInc('click');
        $this->assign('cont_voice',$cont_voice);
        
        //分享内容
        $this->shareTitle=$cont_voice['title'];      // 分享标题
        $this->shareDesc='';       // 分享描述
        $this->shareLink=Config::get('domain').url('voiceDetail',array('id'=>$id,'from'=>$this->fansInfo['wecha_id']));       // 分享链接
        $this->shareImgUrl=Config::get('domain').$cont_voice['pic'];     // 


        //首先判断是否是免费课程
        //判断当前用户是否购买了该课程
        if($cont_voice['type']==1)
        {
            $orders=1;
        }
        else
        {
            //$orders=Db::name('orders')->where(array('user_id'=>$this->fansInfo['id'],'voice_id'=>$id,'status'=>1))->find();
            
            if(!$this->isBuy($cont_voice['curr_id']) && $pfStatus==0)
            {
               $orders=0;
            }
            else
            {
               $orders=1;
            }
        }
        
        
        //读取评论内容
        $cont_comment=Db::name('cont_comment')->where(array('voice_id'=>$id,'del'=>0))->order('id desc')->limit(0,Config::get('page'))->select();
        
        $jxComment=array();
        $comment=array();
        foreach($cont_comment as $k=>$v)
        {
            $user_info=Db::name('user_info')->where('id',$v['user_id'])->find();
            
            $v['portrait']=get_wxheadimg($user_info['portrait'],64);
            $v['wechaname']=$user_info['wechaname'];
            //为1 精选评论
            if($v['status']==1)
            {
               $jxComment[]=$v;
            }
            else
            {
                $comment[]=$v;
            }
        }
        $jxCommentNum=count($jxComment);
        $commentNum=count($comment);
        $commentTotalNum=$jxCommentNum+$commentNum;
        $this->assign('jxComment',$jxComment);
        $this->assign('comment',$comment);
        $this->assign('jxCommentNum',$jxCommentNum);
        $this->assign('commentNum',$commentNum);
        $this->assign('commentTotalNum',$commentTotalNum);
        $this->assign('orders',$orders);
        $this->assign('order_id',$order_id);

        $this->assign('shareTitle',$this->shareTitle);
        $this->assign('shareDesc',$this->shareDesc);
        $this->assign('shareLink',$this->shareLink);
        $this->assign('shareImgUrl',$this->shareImgUrl);
		return $this->fetch();
	}
	
    
    //判断当前用户是否购买某课程
    public function isBuy($curr_id)
    {
        $orders=Db::name('orders')->where(array('user_id'=>$this->fansInfo['id'],'curr_id'=>$curr_id,'nstatus'=>1))->find(); //'status'=>1,

        if(empty($orders))
        {
            return 0;
        }
        else
        {
            return 1;
        }
    }


    //ajax音频详细页评论内容 普通评论
    public function ajaxGetComm()
    {
        $offsize=input('param.offsize');
        $id=input('param.id');
        //读取评论内容
        $cont_comment=Db::name('cont_comment')->where(array('voice_id'=>$id,'status'=>0,'del'=>0))->order('id desc')->limit($offsize,Config::get('page'))->select();
        $returnStr='';
        foreach($cont_comment as $k=>$v)
        {

            $user_info=Db::name('user_info')->where('id',$v['user_id'])->find();
            $v['portrait']=get_wxheadimg($user_info['portrait'],64);
            $v['wechaname']=$user_info['wechaname'];


            $returnStr=$returnStr.'<div class="commentMain">
                <div class="uimg"><img src="'.$v['portrait'].'"/></div>
                <div class="unamc">
                      <div class="uname">'.$v['wechaname'].'</div>
                      <div class="ucm">'.$v['text'].'</div>
                      <div class="ctime">'.date('Y-m-d',$v['create_time']).'</div>
                </div>';
            if(!empty($v['reply_text']))
            {
               $returnStr=$returnStr.'<div class="unamc hf">
                  <div class="uname">'.Config::get('manageName').'</div>
                  <div class="ucm">'.$v['reply_text'].'</div>
                  <div class="ctime">'.date('Y-m-d',$v['reply_time']).'</div>
                </div>';
            }
            $returnStr=$returnStr.'</div>';

        }

        return $returnStr;
    }

    
    //提交订单
    public function addOrder()
    {
        $data=input('param.');
        $data['create_time']=time();
        $cont_curriculum=Db::name('cont_curriculum')->where('id',$data['curr_id'])->find();
        $order_id='xq'.time().rand(1000,9999).rand(1000,9999);  //订单Id
        $data['order_id']=$order_id;
        if(Db::name('orders')->insert($data))
        {
			//获得支付的参数
			$rand = md5(time().rand(1000,9999));
			$param["appid"] = Config::get('Appid');
			$param["openid"] = $this->fansInfo['wecha_id'];
			$param["mch_id"] = Config::get('mchid'); //商户ID
			$param["nonce_str"] = $rand;
			$param["body"] = $cont_curriculum['title'];
			$param["out_trade_no"] = $order_id; //订单编号，要保证不重复
			$param["total_fee"] = $cont_curriculum['price']*100; //支付金额
			$param["spbill_create_ip"] = $_SERVER["REMOTE_ADDR"];
			$param["notify_url"] = Config::get('domain')."/index.php/notify/index.html";
			$param["trade_type"] = "JSAPI";
			
		   //签名算法：http://pay.weixin.qq.com/wiki/doc/api/index.php?chapter=4_3
			$signStr = 'appid='.$param["appid"]."&body=".$param["body"]."&mch_id=".$param["mch_id"]."&nonce_str=".$param["nonce_str"]."&notify_url=".$param["notify_url"]."&openid=".$param["openid"]."&out_trade_no=".$param["out_trade_no"]."&spbill_create_ip=".$param["spbill_create_ip"]."&total_fee=".$param["total_fee"]."&trade_type=".$param["trade_type"];
			$signStr = $signStr."&key=".Config::get('apikey'); //apikey
			$param["sign"] = strtoupper(MD5($signStr));
			$data = '<xml>
					  <appid><![CDATA['.$param["appid"].']]></appid>
					  <openid><![CDATA['.$param["openid"].']]></openid>
					  <mch_id>'.$param["mch_id"].'</mch_id>
					  <nonce_str><![CDATA['.$param["nonce_str"].']]></nonce_str>
					  <body><![CDATA['.$param["body"].']]></body>
					  <out_trade_no><![CDATA['.$param["out_trade_no"].']]></out_trade_no>
					  <total_fee>'.$param["total_fee"].'</total_fee>
					  <spbill_create_ip><![CDATA['.$param["spbill_create_ip"].']]></spbill_create_ip>
					  <notify_url><![CDATA['.$param["notify_url"].']]></notify_url>
					  <trade_type><![CDATA['.$param["trade_type"].']]></trade_type>
					  <sign><![CDATA['.$param["sign"].']]></sign>
					</xml>';
			$postResult = $this->myCurl("https://api.mch.weixin.qq.com/pay/unifiedorder",$data);
			$postObj = simplexml_load_string($postResult, 'SimpleXMLElement', LIBXML_NOCDATA);
			
			$msg = "".$postObj->return_msg;
	
			if($msg == "OK")
			{
			   
			   $result["timestamp"] = time();
               $result["nonceStr"] = "".$postObj->nonce_str;  //不加""拿到的是一个json对象
               $result["package"] = "prepay_id=".$postObj->prepay_id;
               $result["signType"] = "MD5";
			   $paySignStr = 'appId='.Config::get('Appid').'&nonceStr='.$result["nonceStr"].'&package='.$result["package"].'&signType='.$result["signType"].'&timeStamp='.$result["timestamp"];
               $paySignStr = $paySignStr."&key=".Config::get('apikey');
               $result["paySign"] = strtoupper(MD5($paySignStr));
			   return array('code'=>1,'data'=>$result,'order_id'=>$order_id);
			}
		     
        }
        else
        {
           return array('code'=>0); 
        }
        
    }
    //更新微信JS返回的订单状态
    public function upWxJsStatus()
    {
        $order_id=input('param.order_id');
		Db::name('orders')->where('order_id',$order_id)->update(array('status'=>1));
        //if()
        //{
           //return array('code'=>1);
        //}
        //else
        //{
           //return array('code'=>0); 
        //}
    }
    //提交评论
    public function cont_comment()
    {
        $data=input('param.');
        $data['create_time']=time();
        Db::name('cont_comment')->insert($data);
        return array('code'=>1);
    }
    //小纸条
    public function note()
    {
       
       //更新访问小纸条的时间
       Db::name('user_info')->where('id',$this->fansInfo['id'])->update(array('note_time'=>time()));
       //获得该用户和公共的小纸条
       $send_infor=$this->getNoteList();
       $this->assign('send_infor',$send_infor);
       $this->assign('send_inforNum',count($send_infor));
       return $this->fetch();
    }
    
    //小纸条ajax分页
    public function ajaxGetNote()
    {
        $offsize=input('param.offsize');
        $send_infor=$this->getNoteList($offsize);
        $returnStr='';
        foreach($send_infor as $k=>$v)
        {
            
           $returnStr=$returnStr.'<div class="xztlist">
            <div class="xztimg"><img src="'.HTML_STATIC.'/images/xx.jpg" /></div>
            <div class="xztinfo">
                  <div class="xztname">'.config::get('manageName').'</div>
                  <div class="xztmain">'.$v['text'];
                  if(!empty($v['url']))
                  {
                     $returnStr=$returnStr.'<div class="xzturl"><a href="'.$v['url'].'">猛戳这里</a></div>';
                  }

                  $returnStr=$returnStr.'</div>
                  <div class="xzttime">'.date('Y-m-d',$v['create_time']).'</div></div></div>';
        }

        return $returnStr;
    }
    
    public function getNoteList($start=0)
    {
        $offsize=Config::get('page');

        //获得该用户和公共的小纸条
        $send_infor=Db::name('send_infor')->where("send_uid =".$this->fansInfo['id']." or send_uid=0")->limit($start,$offsize)->order('id desc')->select();

        return $send_infor;

    }

	//会员中心
    public function my()
	{
	    
        $this->assign('fansInfo',$this->fansInfo);
        return $this->fetch();
	}

	//会员级别
    public function myvip()
    {
        $cont_vipcont=Db::name('cont_vipcont')->find();
        $this->assign('cont_vipcont',$cont_vipcont);

        return $this->fetch();
    }
    //个人信息
    public function myinfo()
    {
        
        $this->assign('fansInfo',$this->fansInfo);

        return $this->fetch();
    }

    //更新个人信息
	public function myinfoUpdate()
    {
        $data=input('param.');
        Db::name('user_info')->where('wecha_id',$data['wecha_id'])->update($data);
        return array('code'=>'1');
    }

    //我的购买记录
    public function mybuy()
    {
       $orders=Db::name('orders')->order('id desc')->where(array('user_id'=>$this->fansInfo['id'],'nstatus'=>1))->select();  //'status'=>1,
       //读取订单音频的名称
       foreach($orders as $k=>$v)
       {
          $cont_curriculum=Db::name('cont_curriculum')->where('id',$v['curr_id'])->find();
          $orders[$k]['title']=$cont_curriculum['title'];
          $orders[$k]['pic']=$cont_curriculum['pic'];
          //$orders[$k]['type']=$cont_curriculum['type'];
          //$orders[$k]['click']=$cont_curriculum['click'];
          //读取评论数
          $cont_comment=Db::name('cont_comment')->where(array('voice_id'=>$v['voice_id'],'del'=>0))->count();
          $orders[$k]['commentNum']=$cont_comment;
       }
      if(empty($orders)) $orders='';
       $this->assign('orders',$orders);
       return $this->fetch(); 
    }

    //我的评论
    public function mycomm()
    {
       $cont_comment=Db::name('cont_comment')->order('id desc')->where('user_id',$this->fansInfo['id'])->limit('20')->select(); 
       $this->assign('cont_comment',$cont_comment);
       return $this->fetch(); 
    }

    //意见反馈
    public function myOpinion()
    {
        $request=request();
        if($request->method()=='POST')
        {
            $data=input('param.');
            $data['wecha_id']=$this->fansInfo['wecha_id'];
            $data['create_time']=time();
            Db::name('sys_opinion')->insert($data);
            return array('code'=>'1');
        }
        else
        {
            //授权
            $this->getUserInfo();
            $this->assign('fansInfo',$this->fansInfo);
            return $this->fetch(); 
        }

        
    }


    //图文详细页展示
    public function arcDetail()
    {
        $id=input('param.id');
        
        if(empty($id) && !is_int($id))
        {
            echo "参数错误！";
            exit;
        }
        //点击次数加1
		Db::name('img_text')->where('id',$id)->setInc('click');
        $img_text=Db::name('img_text')->field('id,title,text,update_time')->where('id',$id)->find();
        $this->assign('img_text',$img_text);
        return $this->fetch();
    }

    //联系我们
    public function contact()
    {
        $cont_contact=Db::name('cont_contact')->find();
        $this->assign('cont_contact',$cont_contact);
        return $this->fetch();
    }
    //徐箐老师简介
    public function introuduction()
    {
        $cont_introuduction=Db::name('cont_introuduction')->find();
        $this->assign('infos',$cont_introuduction);
        return $this->fetch('infos');
    }
    //课程简介
    public function course()
    {
        $cont_course=Db::name('cont_course')->find();
        $this->assign('infos',$cont_course);
        return $this->fetch('infos');
    }

    //记录分享的记录
    public function addShare()
    {
        $data=input('param.');
        $data['share_time']=time();
        Db::name('share_list')->insert($data);
    }
	
	public function myCurl($url,$data)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$tmpInfo = curl_exec($ch);
		if (curl_errno($ch)) {
			return $tmpInfo;
		}
		curl_close($ch);
		return $tmpInfo;
    }

}