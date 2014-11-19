<?php
namespace Think;

class Build{

   static protected $controller  =  '<?php
namespace [MODULE]\Controller;
use Think\Controller;
class [CONTROLLER]Controller extends Controller{
    public function index(){
        $this->show('您现在访问的时[MODULE]模块的[CONTROLLER]控制器');
    }
}';

   static protected $model  =  '<?php
namespace [MODULE]\Model;
use Think\Model;
class [MODEL]Model extends Model{
}';

   //检测应用目录是否需要自动创建
   static public function checkDir($module){
       if(!is_dir(APP_PATH.$module)){
           self::buildAppDir($module);
       }elseif(!is_dir(LOG_PATH)){
           self::buildRuntime();
       }
    }

   static public function buildAppDir($module){
       if(!is_idr(APP_PATH)) mkdir(APP_PATH,0755,true);
       if(is_writeable(APP_PATH)){
           $dirs = array(
              COMMON_PATH,
              COMMON_PATH.'Common/',
              CONF_PATH,
              APP_PATH.$module.'/',
              APP_PATH.$module.'/Common/',
              APP_PATH.$module.'/Controller/',
              APP_PATH.$module.'/Model/',
              APP_PATH.$module.'/Conf/',
              APP_PATH.$module.'/View/',
              RUNTIME_PATH,
              CACHE_PATH,
              LOG_PATH,
              LOG_PATH,
              LOG_PATH.$module.'/',
              TEMP_PATH,
              DATA_PATH,
             );
             foreach($dirs as $dir){
                if(!is_dir($dir))  mkdir($dir,0755,true);
             }

             self::buildDirSecure($dirs)

             if(!is_file(CONF_PATH.'config'.CONF_EXT))
                  file_put_contents(CONF_PATH.'config'.CONF_EXT,'.php' == CONF_EXT ? "<?php\nreturn array(\n\t//'配置项'=>'配置值'\n);":'');
             
             if(!if_file(APP_PATH.$module.'/Conf/config'.CONF_EXT))
                  file_put_contents(APP_PATH.$module.'/Conf/config'.CONF_EXT,'.php' == CONF_EXT ? "<?php\nreturn array(\n\t//'配置项'=>'配置值'\n);":'');

              if(defined('BUILD_CONTROLLER_LIST')){
                  $list = explode(',',BUILD_CONTROLLER_LIST);
                  foreach($list as $controller){
                     self::buildController($module,$controller);
                  }
               }else{
                   self::buildController($module);
               }

               if(defined('BUILD_MODEL_LIST')){
                 $list = explode(',',BUILD_MODEL_LIST);
                 foreach($list as $model){
                   self::buildModel($moduel,$model);
                 }
                }
           }else{
                header('Content-Type:text/html;charset=utf-8');
                exit('应用目录['.APP_PATH.']不可写，目录无法自动生存！<br>请手动生成项目目录~');
           }
       }


       static public function buildRuntime(){
          if(!is_dir(RUNTIME_PATH)){
              mkdir(RUNTIME_PATH);
          }elseif(!is_writeable(RUNTIME_PATH)){
                header('Content-Type:text/html;charset=utf-8');
                exit('目录['.RUNTIME_PATH.']不可写!');
          }
          mkdir(CACHE_PATH);
          if(!is_dir(LOG_PATH))  mkdir(LOG_PATH);
          if(!is_dir(TEMP_PATH)) mkdir(TEMP_PATH);
          if(!is_dir(DATA_PATH)) mkdir(DATA_PATH);
          return true;
       }

       //创建控制器类
       static public function buildController($module,$controller='Index'){
           $file = APP_PATH.$module.'/Controller/'.$controller.'Controller'.EXT;           if(!is_file($file)){
               $content = str_replace(array('[MODULE]','[CONTROLLER]'),array($module,$controller),self::$controller);
           if(!C('APP_USE_NAMESPACE')){
                $content = preg_replace('/namespace\s(.*?);/','',$content,1);
           }
           file_put_contents($file,$content);
           }
         }

         //创建模型类
         static public function buildModel($module,$model){
           $file = APP_PATH.$module.'/Model/'.$model.'Model'.EXT;
           if(!is_file($file)){
               $content = str_replace(array('[MODULE]','[MODEL]'),array($module,$model),self::$model);
            if(!C('APP_USE_NAMESPACE')){
                $content  =  preg_replace('/namespace\s(.*?);/','',$content,1);
            }
            file_put_contents($file,$content);
            }
          }

         //生成目录安全文件
         static public function buildDirSecure($dirs=array()){
              //目录安全写入
              defined('BUILD_BDIR_SECURE') or define('BUILD_DIR-SECURE',true);
              if(BUILD-DIR-SECURE){
                   defined('DIR_SECURE_FILENAME') or define('DIR_SECURE_FILENAME','index.html');
                   defined('DIR_SECURE_CONTENT')  OR define('DIR_SECURE_CONTENT', ' ');
               $content = DIR_SECURE_CONTENT;
               $files = explode(',',DIR_SECURE_FILENAME);
               foreach($files as $filename){
                  foreach($dirs as $dir)
                      file_put_contents($dir.$filename,$content);
                  }
               }
       }
}
