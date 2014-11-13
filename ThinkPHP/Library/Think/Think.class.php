<?php
//作者：陈燃

namespace Think;

class Think {
    
    //类映射
    private static $_map = array();

    //实例化对象
    private static $_instance = array();

    //应用程序初始化
    static public function start(){
    //注册autoload方法
    spl_autoload_register('Think\Think::autoload');
    //设定错误和异常处理
    register_shutdown_function('Think\Think::fatalError');
    set_error_handler('Think\Think::appError');
    set_exception_handler('Think\Think::appException');

    //初始化文件存储方式
    Storage::connect(STORAGE_TYPE);

    $runtimefile = RUNTIME_PATH.APP_MODE.'~runtime.php';
    if(!APP_DEBUG && Storage::has($runtimefile)){
        Storage::load($runtimefile);
    }else{
        if(Storage::has($runtimefile))
             Storage::unlink($runtimefile);
        $content = '';
        //读取应用模式
        $mode = include if_file(CONF_PATH.'core.php')?CONF_PATH.'core.php':MODE_PATH.APP_MODE.'.php';
       //加载核心文件
       foreach ($mode['core'] as $file){
           if(is_file($file)){
               include $file;
               if(!APP_DEBUG) $content .= compile($file);
            }
        }

        //加载应用模式配置文件
        foreach ($mode['config'] as $key=>$file){
            is_numeric($key)?C(load_config($file)):C($key,load_config($file));
        }

        //读取当前应用模式对应对配置文件
        if('common' != APP_MODE && is_file(CONF_PATH.'config_'.APP_MODE.CONF_EXT))
             C(load_config(CONF_PATH.'config_'.APP_MODE.CONF_EXT));

        //加载模式别名定义
        if(isset($mode['alias'])){
              self::addMap(is_array($mode['alias'])?$mode['alias']:include $mode['alias']);
        }

        //加载应用别名别名定义文件
        if(is_file(CONF_PATH.'alias.php'))
              self::addMap(include CONF_PATH.'alias.php');

        //加载模式行为定义
        if(isset($mode['tags'])){
             Hook::import(is_array($mode['tags'])?$mode['tags']:include $mode['tags']);
        }

        //加载应用行为定义
        if(is_file(CONF_PATH.'tags.php'))
           //允许应用增加开发模式配置定义
           Hook::import(include CONF_PATH.'tags.php');

        //加载框架底层语言包
        L(include THINK_PATH.'Lang/'.strtolower(C('DEFAULT_LANG')).'.php');

        if(!APP_DEBUG){
           $content .= "\nnamespace { Think\Think::addMap(".var_export(self::$_map,true).");";
           $content .= "\nL(".var_export(L(),true).");\nC(".var_export(C(),true).');Think\Hook::import('.var_export(Hook::get(),true),true).');}';
           Storage::put($runtimefile,strip_whitespace('<?php '.$content));
         }else{
            //调试模式加载系统默认对配置文件
           C(include THINK_PATH.'Conf/debug.php');
            //读取应用调试配置文件
           if(is_file(CONF_PATH.'debug'.CONF_EXT))
               C(include CONF_PATH.'debug'.CONF_EXT);
           }
      }

     //读取当前应用状态对应对配置文件
     if(APP_STATUS && is_file(CONF_PATH.APP_STATUS.CONF_EXT))
         C(include CONF_PATH.APP_STATUS.CONF_EXT);

     //设置系统时区
     date_default_timezone_set(C('DEFAULT_TIMEZONE'));

     //检查应用目录结构 如果不存在则自动创建
     if(C('CHECK_APP_DIR')){
         $module = defined('BIND_MODULE') ? BIND_MODULE : C('DEFAULT_MODULE');
         if(!is_dir(APP_PATH.$module) || !is_dir(LOG_PATH)){
            //检测应用目录结构
            Build::checkDir($module);
          }
       }

      //记录加载文件时间
      G('loadTime');
      //运行应用
      App::run();
    }
