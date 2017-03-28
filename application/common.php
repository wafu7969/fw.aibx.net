<?php
// 应用公共文件
// 
// 
//get请求
function httpGet($url) 
{
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_TIMEOUT, 500);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($curl, CURLOPT_URL, $url);

    $res = curl_exec($curl);
    curl_close($curl);

    return $res;
}

//获得微信不同大小的头像
function get_wxheadimg($url,$size)
{
   $url = substr($url,0,strlen($url)-1);
   $url=$url.$size;
   return $url;
}

//通过秒获得分钟
function get_minute($second)
{
   if($second<=60)
   {
       return $second.'秒';
   }
   else
   {
      $minute=floor($second/60);
      $second=fmod($second,60);  
   }
   if($second==0)
   {
     return $minute.'分钟';
   }
   else
   {
      return $minute.'分钟'.$second.'秒'; 
   }
   
}


//post
function curl_post($url, $data)
{
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
    return $data = curl_exec($ch);
}

//格式化时间
function formatDate($datetime)
{
   
   if($datetime>=strtotime(date('Y-m-d')))
   {
       return '今天';
   }
   elseif($datetime<strtotime(date('Y-m-d')) && $datetime>=strtotime(date("Y-m-d",strtotime("-1 day"))))
   {
       return '昨天';
   }
   else
   {
       return date('Y-m-d',$datetime);
   }
}

/**
 * 获取当前URL
 * @return string
 */
function getSelfUrl($params = array(), $url = '')
{
	$secure = isset($_SERVER['HTTPS']) && (strcasecmp($_SERVER['HTTPS'],'on')===0 || $_SERVER['HTTPS']==1)	|| isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strcasecmp($_SERVER['HTTP_X_FORWARDED_PROTO'],'https')===0;
	$hostInfo = '';
	if($secure)
    {
		$http='https';
	}
    else
    {
		$http='http';
	}
	if(isset($_SERVER['HTTP_HOST']))
    {
		$hostInfo=$http.'://'.$_SERVER['HTTP_HOST'];
	}
    else
    {
		$hostInfo=$http.'://'.$_SERVER['SERVER_NAME'];
		if ($secure) {
			$port = isset($_SERVER['SERVER_PORT']) ? (int)$_SERVER['SERVER_PORT'] : 443;
		} else {
			$port = isset($_SERVER['SERVER_PORT']) ? (int)$_SERVER['SERVER_PORT'] : 80;
		}
		if(($port!==80 && !$secure) || ($port!==443 && $secure)) {
			$hostInfo.=':'.$port;
		}
	}
	$requestUri = '';
	if(isset($_SERVER['HTTP_X_REWRITE_URL'])) {// IIS
		$requestUri=$_SERVER['HTTP_X_REWRITE_URL'];
	} elseif (isset($_SERVER['REQUEST_URI'])) {
		$requestUri=$_SERVER['REQUEST_URI'];
		if(!empty($_SERVER['HTTP_HOST'])) {
			if(strpos($requestUri,$_SERVER['HTTP_HOST'])!==false) {
				$requestUri=preg_replace('/^\w+:\/\/[^\/]+/','',$requestUri);
			}
		}
		else {
			$requestUri=preg_replace('/^(http|https):\/\/[^\/]+/i','',$requestUri);
		}
	} elseif(isset($_SERVER['ORIG_PATH_INFO'])) { // IIS 5.0 CGI
		$requestUri=$_SERVER['ORIG_PATH_INFO'];
		if(!empty($_SERVER['QUERY_STRING'])) {
			$requestUri.='?'.$_SERVER['QUERY_STRING'];
		}
	} else {
		exit('没有获取到当前的url');
	}
	if (empty($url)) {		
		$url =  $hostInfo.$requestUri;
	}
	$parseUrl = parse_url($url);
	if(!empty($parseUrl['query']))
	{
	  parse_str(htmlspecialchars_decode($parseUrl['query']), $query);
	  foreach ($params as $key => $param) {
		  $value = isset($query[$param]) ? $query[$param] : '';
		  if (1 == count($query)) {
			  $value = '\?'.$param.'='.$value;
		  } else {
			  $value = '&'.$param.'='.$value.'|'.$param.'='.$value.'&';
		  }
		  $url = preg_replace("/$value/i", '', $url);
	  }
	}

	return $url;
}
