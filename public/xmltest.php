<?php
$xml='<xml><return_code><!--[CDATA[SUCCESS]]--></return_code>
<return_msg><!--[CDATA[OK]]--></return_msg>
<appid><!--[CDATA[wx9734cbdef33e72a8]]--></appid>
<mch_id><!--[CDATA[1293463001]]--></mch_id>
<nonce_str><!--[CDATA[mR19P4VeBiyx9N7m]]--></nonce_str>
<sign><!--[CDATA[1ED7C0FB48D490380D8048898C84060B]]--></sign>
<prepay_id><!--[CDATA[wx20170206152032ea013111c70981867420]]--></prepay_id>
<trade_type><!--[CDATA[JSAPI]]--></trade_type>
</xml>';


print_r(json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true));

?>