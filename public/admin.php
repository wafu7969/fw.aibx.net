<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

// [ 应用入口文件 ]

// 定义应用目录
define('APP_PATH', __DIR__ . '/../application/');
define('BIND_MODULE','admin');
//定义公用的常量
define('HTML_STATIC','/static/admin');
//绑定微信对接的接口URL地址
define('WXBIND_URL','http://wx.aibx.net/index.php/Weixin/index/token/');
// 加载框架引导文件
require __DIR__ . '/../thinkphp/start.php';