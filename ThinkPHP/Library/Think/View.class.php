<?php
namespace Think;

class View{

   protected $tVar = array();

   protected $theme = '';
   
   //模板变量赋值
   public function assign($name,$value=''){
     if(is_array($name)){
          $this->tVar = array_merge($this->tVar,$name);
      }else{
          $this->tVar[$name] = $value;
      }
   }

   //取得模板变量的值
   public function get($name=''){
       if('' === $name){
           return $this->tVar;
       }
       return isset($this->tVar[$name])?$this->tVar[$name]:false;
   }

   //加载模板和页面输出 可以返回输出内容
   public function display($templateFile='',$charset='',$contentType='',$content='',$prefix=''){
        G('viewStartTime');
        //视图开始标签
        Hook::listen('view_begin',$templateFile);
        //解析并获取模板内容
       $content = $this->fetch($templateFile,$content,$prefix);
        //输出模板内容
       $this->render($content,$charset,$contentType);
        //视图结束标签
        Hook::listen('view_end');
    }

    //输出内容文本 可以包括HTML
    private function render($content,$charset='',$contentType=''){
        if(empty($charset))  $charset = C('DEFAULT_CHARSET');
        if(empty($contentType)) $contentType = C('TMPL_CONTENT_TYPE');
        //页面字符编码
        header('Content-Type:'.$contentType.'; charset='.$charset);
        header('Cache-control: '.C('HTTP_CACHE_CONTROL'));//页面缓存控制
        header('X-Powered-By:ThinkPHP');
        //输出模板文件
        echo $content;
    }

    //解析和获取模板内容 用于输出
    public function fetch($templateFile='',$content='',$prefix=''){
        if(empty($content)){
           $templateFile = $this->parseTemplate($templateFile);
           //模板文件不存在则直接返回
           if(!is_file($templateFile)) E(L('_TEMPLATE_NOT_EXIST_').':'.$templateFile);
           }
           ob_start();
           ob_implicit_flush(0);
           if('php' == strtolower(C('TMPL_ENGING_TYPE'))){ //使用PHP原生模板
              $_content = $content;
              //模板阵列变量分解成独立变量
              extract($this->tVar,EXTR_OVERWRITE);
              //直接载入PHP模板
              empty($_content)?include $templateFile:eval('?>'.$_content);
          }else{
              //视图解析标签
               $params = array('var'=>$this->tVar,'file'=>$templateFile,'content'=>$content,'prefix'=>$prefix);
               Hook::listen('view_parse',$params);
          }
          //获取并清空缓存
          $content = ob_get_clean();
          Hook::listen('view_filter',$content);
          //输出模板文件
          return $content;
     }

     //自动定位模板文件
     public function parseTemplate($template=''){
           if(is_file($template)){
                return $template;
           }
           $depr  = C('TMPL_FILE_DEPR');
           $template = str_replace(':',$depr,$template);
           //获取当前主题名称
           $theme = $this->getTemplateTheme();

           //获取当前模块
           $module = MODULE_NAME;
           if(strpos($template,'@')){//跨模块调用模板文件
               list($module,$template) = explode('@',$template);
           }
           //获取当前主题对模板路径
           if(!defined('THEME_PATH')){
                if(C('VIEW_PATH')){//模块设置独立的视图目录
                    $tmplPath = C('VIEW_PATH');
                }else{
                    $tmplPath = defined('TMPL_PATH')?TMPL_PATH.$module.'/':APP_PATH.$module.'/'.C('DEFAULT_V_LAYER').'/';
                }
                define('THEME_PATH',$tmplPath.$theme);
           }

           //分析模板文件规则
           if('' == $template){
                //如果模板文件名为空 按照默认规则定位
                $template = CONTROLLER_NAME . $depr .ACTION_NAME;
           }elseif(false === strpos($template,$depr)){
                $template = CONTROLLER_NAME . $depr . $template;
           }
           $file = THEME_PATH.$template.C('TMPL_TEMPLATE_SUFFIX');
           if(C('TMPL_LOAD_DEFAULTTHEME') && THEME_NAME != C('DEFAULT_THEME') && !is_file($file)){
               //找不到当前主题模板的时候定位默认主题中的模板
               $file = dirname(THEME_PATH).'/'.C('DEFAULT_THEME').'/'.$template.C('TMPL_TEMPLATE_SUFFIX');
            }
            return $file;
        }

        //设置当前输出对模板主题
        public function theme($theme){
           $this->theme = $theme;
           return $this;
       }

       //获取当前对模板主题
       private function getTemplateTheme(){
          if($this->theme){ //制定模板主题
              $theme = $this-> theme;
          }else{
              //获取模板主题名称
              $theme = C('DEFAULT_THEME');
              if(C('TMPL_DETECT_THEME')){//自动侦测模板主题
                  $t = C('VAR_TEMPLATE');
                  if(isset($_GET[$t])){
                      $theme - $_GET[$t];
                  }elseif(cookie('think_template')){
                      $theme = cookie('think_template');
                  }
                  if(!in_array($theme,explode(',',C('THEME_LIST')))){
                      $theme = C('DEFAULT_THEME');
                  }
                  cookie('think_template',$theme,86400);
               }
          }
          define('THEME_NAME') || define('THEME_NAME',$theme); //当前模板主题名词
          return $theme?$theme . '/':'';
     }
}
