<?php
namespace app\index\controller;
use think\Controller;
use think\Db;
use think\Config;

class Notify extends Controller
{

    //支付的回调函数
    public function index()
    {

        $postStr = $GLOBALS["HTTP_RAW_POST_DATA"];
        $postObj = simplexml_load_string($postStr,'SimpleXMLElement',LIBXML_NOCDATA);
        
        $order_id = $postObj->out_trade_no."";   //商户系统的订单号，与请求一致。
        $transaction_id = $postObj->transaction_id."";  //微信支付订单号
        //$sign = $postObj->sign;  //签名
        
        Db::name('orders')->where('order_id',$order_id)->update(array('nstatus'=>1,'transaction_id'=>$transaction_id));
        //判断该次购买的课程是否需要升级VIP
        $orders=Db::name('orders')->field('curr_id,user_id')->where('order_id',$order_id)->find();
        $cont_curriculum=Db::name('cont_curriculum')->field('upvip')->where('id',$orders['curr_id'])->find();
        if($cont_curriculum['upvip']==1)
        {
             Db::name('user_info')->where('id',$orders['user_id'])->setInc('vip');
        }
    }
	
	

}
?>