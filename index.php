<?php
//BTP(better than tp)一款对thinkphp进行改进对框架
//作者:陈燃

//检测PHP版本号
if(version_compare(PHP_VERSION,'5.3.0','<')) die('需要您对PHP版本高于5.3.0');

//开启调试模式，建议在生产环境将其注释或设为false
define('APP_DEBUG',True);

//定义应用目录
define('APP_PATH','./Application/');

//引入ThinkPHP入口文件
require './ThinkPHP/ThinkPHP.php';
