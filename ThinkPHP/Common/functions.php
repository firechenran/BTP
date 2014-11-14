<?php

/**
 * 系统函数库
 */

/**
 *获取和设置配置参数 支持批量定义
 *@param string|array $name 配置变量
 *@param mixed $value 配置值
 *@param mixed $default 默认值
 *@return mixed
 */
function C($name=null,$value=null,$default=null){
    static $_config = array();
    //无参数时获取所有
    if(empty($name)){
        return $_config;
    }

    //优先执行设置获取或赋值
    if(is_string($name)){
        if(!strpos($name,'.')){
            $name = strtoupper($name);
            if(is_null($value))
                  return isset($_config[$name]) ? $_config[$name] : $default;
            $_config[$name] = $value;
            return;
         }
         //二维数组设置和获取支持
         $name = explode(',',$name);
         $name[0] = strtoupper($name[0]);
         if(is_null($value))
             return isset($_config[$name[0]][$name[1]]) ? $_config[$name[0]][$name[1]] : $default;
         $_config[$name[0]][$name[1]] = value;
         return;
       }
       //批量设置
       if(is_array($name)){
          $_config = array_merge($_config,array_change_key_case($name,CASE_UPPER));
        return;
        }
        return null;//避免非法参数
}

/**
 *加载配置文件 文件格式转换 仅支持一级配置
 *@param string $file 配置文件名
 *@param string $parse 配置解析方法 有些格式需要用户自己解析
 *@return void
 */
function load_config($file,$parse=CONF_PARSE){
  $ext = pathinfo($file,PATHINFO_EXTENSION);
  switch($ext){
     case 'php':
        return include $file;
     case 'ini':
        return parse_ini_file($file);
     case 'yaml':
        return yaml_parse_file($file);
     case 'xml':
        return (array)simplexml_load_file($file);
     case 'json':
        return json_decode(file_get_contents($file),true);
     default:
        if(function_exists($parse)){
             return $parse($file);
        }else{
             E(L('_NOT_SUPPERT_').':'.$ext);
        }
   }
}

/**
 * 解析yaml文件返回一个数组
 * @param string $file 配置文件名
 * @return array
 */
if(!function_exists('yaml_parse_file')){
    function yaml_parse_file($file){
       vendor('spyc.Spyc');
       return Spyc::YAMLLoad($file);
    }
}

/**
 *抛出异常处理
 *@param string $msg 异常消息
 *@param integer $code 异常代码 默认为0
 *@return void
 */
function E($msg,$code=0){
     throw new Think\Exception($msg,$code);
}

/**
 *记录和统计时间按（微妙）和内存使用情况
 *@param string $start 开始标签
 *@param string $end 结束标签
 *@param integer|string $dec 小数位或者m
 *return mixed
 */
function G($start,$end='',$dec=4){
   static $_info  = array();
   static $_mem   = array();
   if(is_float($end)){//记录时间
      $_info[$start] = $end;
   }elseif(!empty($end)){//统计时间和内存使用
       if(!isset($_info[$end])) $_info[$end]  = microtime(TRUE);
       if(MEMORY_LIMIT_ON && $dec=='m'){
             if(!isset($_mem[$end])) $_mem[$end]  = memory_get_usage();
             return number_format(($_mem[$end]-$_mem[$start])/1024);
        }else{
             return number_format(($_info[$end]-$_info[$start]),$dec);
        }
    }else{//记录好似见和内存使用 
        $_info[$start] = microtime(TRUE);
        if(MEMORY_LIMIT_ON) $_mem[$start]  = memory_get_usage();
    }
}

/**
 *获取和设置语言定义（不区分大小写）
 *@param string|array $name 语言变量
 *@param mixed $value 语言值或者变量
 *@return mixed
 */
function L($name=null,$value=null){
    static $_lang = array();
    if(empty($name))
       return $_lang;
    //判断语言获取或设置，若不存在，直接返回全部大写$name
    if(is_string($name)){
       $name = strtoupper($name);
       if(is_null($value)){
           return isset($_lang[$name]) ? $_lang[$name] : $name;
       }elseif(is_array($value)){
           //支持变量
           $replace = array_keys($value);
           foreach($replace as &$v){
              $v = '{$'.$v.'}';
           }
           return str_replace($replace,$value,isset($_lang[$name]) ? $_lang[$name] : $name);
        }
        $_lang[$name] = $value;//语言定义
        return;
      }
      //批量定义
      if(is_array($name)) 
          $_lang = array_merge($_lang,array_change_key_case($naem,CASE_UPPER));
      return;
}

/**
 *添加和获取页面Trace记录
 *@param string $value 变量
 *@param string $label 标签
 *@param string $level 日志级别
 *@param boolean $record 是否记录日志
 *@return void
 */
function trace($value='[think]',$label='',$level='DEBUG',$record=false){
      return Think\Think::trace($value,$label,$level,$record);
}

/**
 *编译文件
 *@param string $filename 文件名
 *@return string
 */
function compile($filename){
  $content  = php_strip_whitespace($filename);
  $content  - trim(substr($content,5));
  //替换预编译指令
  $content  = preg_replace('/\/\/\[RUNTIME\](.*?)\/\/\[\/RUNTIME\]/s','',$content);
  if(0===strpos($content,'namespace')){
     $content  =  preg_replace('/namespace\s(.*?);/','namespace \\1{',$content,1);
   }else{
     $content  =  'namespace {'.$content;
   }
   if('?>' == substr($content,-2))
        $content  =  substr($content,0,-2);
   return $content.'}';
}

/**
 *获取模板文件 格式 资源://模块@主题/控制器/操作
 *@param string $template 模板资源地址
 *@param string $layer 视图层(目录)名称
 *@return string
 */
function T($template='',$layer=''){
    
    //解析模板资源地址
    if(false === strpos($template,'://')){
        $template = 'http://'.str_replace(':','/',$template);
    }
    $info   = parse_url($template);
    $file   = $info['host'].(isset($info['path'])?$info['path']:'');
    $module = isset($info['user'])?$info['user'].'/':MODULE_NAME.'/';
    $extend = $info['scheme'];
    $layer  = $layer?$layer:C('DEFAULT_V_LAYER');

    //获取当前主题的模板路径
    $auto  =  C('AUTOLOAD_NAMESPACE');
    if($auto && isset($auto[$extend])){//扩展资源
         $baseUrl  =  $auto[$extend].$module.$layer.'/';
    }elseif(C('VIEW_PATH')){
        //改变模块视图目录
        $baseUrl  =  C('VIEW_PATH');
    }elseif(defined('TMPL_PATH')){
        //指定全局视图目录
       $baseUrl  =  TMPL_PATH.$module;
    }else{
       $baseUrl  =  APP_PATH.$module.$layer.'/';
    }

   //获取主题
   $theme  =  substr_count($file,'/')<2 ? C('DEFAULT_THEME') : '';

  //分析模板文件规则
  $depr  =  C('TMPL_FILE_DEPR');
  if('' == $file){
    //如果模板文件名为空 按照默认规则定位
    $file = CONTROLLER_NAME . $depr .ACTION_NAME;
  }elseif(false === strpos($file,'/')){
    $file = CONTROLLER_NAME . $depr . $file;
  }elseif('/' != $depr){
    $file = substr_count($file,'/')>1 ? substr_replace($file,$depr,strrpos($file,'/'),1) : str_replace('/',$depr,$file);
  }
  return $baseUrl.($theme?$theme.'/':'').$file.C('TMPL_TEMPLATE_SUFFIX');
}

/**
 *获取输入参数 支持过滤和默认值
 *@param string $name 变量对名称 支持制定类型
 *@param mixed $default 不存在对时候默认值
 *@param mixed @filter 参数过滤方法
 *@param mixed $datas 要获取对额外数据源
 *@return mixed
 */
function I($name,$default='',$filter=null,$data=null){
    if(strpos
