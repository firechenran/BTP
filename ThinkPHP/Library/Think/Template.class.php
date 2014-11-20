<?php
namespace Think;

class Template{
    //模板文件中引入对标签库列表
    protected $tagLib  = array();
    //当前模板文件
    protected $templateFile = '';
    //模板变量
    public $tVar = array();
    public $config = array();
    private $literal = array();
    private $block = array();

    public function __construct(){
       $this->config['cache_path']  = C('CACHE_PATH');
       $this->config['template_suffix'] = C('TMPL_TEMPLATE_SUFFIX');
       $this->config['cache_suffix'] = C('TMPL_CACHFILE_SUFFIX');
       $this->config['tmpl_cache'] = C('TMPL_CACHE_ON');
       $this->config['cache_time'] = C('TMPL_CACHE_TIME');
       $this->config['taglib_begin'] = $this->stripPreg(C('TAGLIB_BEGIN'));
       $this->config['taglib_end'] = $this->stripPreg(C('TAGLIB_END'));
       $this->config['tmpl_begin'] = $this->stripPreg(C('TMPL_L_DELIM'));
       $this->config['tmpl_end'] = $this->stripPreg(C('TMPL_R_DELIM'));
       $this->config['default_tmpl'] = C('TEMPLATE_NAME');
       $this->config['layout_item'] = C('TMPL_LAYOUT_ITEM');
    }

    private function stripPreg($str){
        return str_replace(
           array('{','}','(',')','|','[',']','-','+','*','.','^','?'),
           array('\{','\}','\(','\)','\|','\[','\]','\-','\+','\*','\.','\^','\?'),
           $str);
    }

    //模板变量获取和设置
    public function get($name){
       if(isset($this->tVar[$name]))
            return $this->tVar[$name];
       else
            return false;
    }

    public function set($name,$value){
        $this->tVar[$name] = $value;
    }

    //加载模板
    public function fetch($templateFile,$templateVar,$prefix=''){
       $this->tVar  =  templateVar;
       $templateCacheFile = $this->loadTemplate($templateFile,$prefix);
       Storage::load($templateCacheFile,$this->tVar,null,'tpl');
    }

    //加载主模板并缓存
    public function loadTemplate($tmplTemplateFile,$prefix=''){
       if(is_file($tmplTemplateFile)){
           $this->templateFile = $tmplTemplateFile;
           //读取模板文件内容
           $tmplContent = file_get_contents($tmplTemplateFile);
        }else{
           $tmplContent = $tmplTemplateFile;
        }

        //根据模板文件名定位缓存文件
        $tmplCacheFile = $this->config['cache_path'].$prefix.md5($tmplTemplateFile).$this->config['cache_suffix'];

        //判断是否启用布局
        if(C('LAYOUT_ON')){
           if(false !== strpos($tmplContent,'{__NOLAYOUT__}')){//可以单独定义不使用布局
               $tmplContent = str_replace('{__NOLAYOUT__}','',$tmplContent);
            }else{//替换布局对主体内容 
               $layoutFile = THEME_PATHA.C('LAYOUT_NAME').$this->config['template_suffix'];
               $tmpContent = str_replace($this->config['layout_item'],$tmplContent,file_get_contents($layoutFile));
             }
       }
       //编译模板内容
       $tmplContent = $this->compiler($tmplContent);
       Storage::put($tmplCacheFile,trim($tmplContent),'tpl');
       return $tmplCacheFile;
    }

    //编译模板文件内容
    protected function compiler($tmplContent){
        //模板解析
        $tmplContent = $this->parse($tmplContent);
        //还原被替换的Literal标签
        $tmplContent = preg_replace_callback('/<!--###literal(\d+)###-->/is',array($this,'restoreLireral'),$tmplContent);
        //添加安全代码
        $tmplContent = '<?php if (!defined(\'THINK_PATH\')) exit();?>'.$tmplContent;
        //优化生成的php代码
       $tmplContent = str_replace('?><?php','',$tmplContent);
        //模板编译过滤标签
        Hook::listen('template_filter',$tmplContent);
        return strip_whitespace($tmplContent);
      }

      //模板解析入口
      public function parse($content){
        //内容为空不解析
        if(empty($content)) return '';
        $begin = $this->config['taglib_begin'];
        $end   = $this->config['taglib_end'];
       //检查include语法
        $content = $this->parseInclude($content);
       //检查PHP语法
        $content = $this->parsePhp($content);
       //首先替换literal标签内容
        $content = preg_replace_callback('/'.$begin.'literal'.$end.'(.*?)'.$begin.'\/literal'.$end.'/is',array($this,'parseLiteral'),$content);

       //获取需要引入的标签库列表
       if(C('TAGLIB_LOAD')){
           $this->getIncludeTagLib($content);
           if(!empty($this->tagLib)){
              //对导入的TagLib进行解析
              foreach($this->tagLib as $tagLibName){
                  $this->parseTagLib($tagLibName,$content);
              }
            }
         }

         //预先加载的标签库 无需在每个模板中使用taglib标签加载 但必须使用标签库XML前缀
         if(C('TAGLIB_PRE_LOAD')){
              $tagLibs = explode(',',C('TAGLIB_PRE_LOAD'));
              foreach($tagLibs as $tag){
                  $this->parseTagLib($tag,$content);
              }
          }

          //内置标签库 无需使用taglib标签导入就可以使用 并且不许要使用标签库XML前缀
          $tagLibs = explode(',',C('TAGLIB_BUILD_IN'));
          foreach($tagLibs as $tag){
              $this->parseTagLib($tag,$content,true);
          }
          //解析普通模板标签{tagName}
          $content = preg_replace_callback('/('.$this->config['tmpl_begin'].')([^\d\s'.$this->config['tmpl_begin'].$this->config['tmpl_end'].'].+?)('.$this->config['tmpl_end'].')/is',array($this,'parseTag'),$content);
           return $content;
       }

        //检查PHP语法
        protected function parsePhp($content){
            if(ini_get('short_open_tag')){
              $content = preg_replace('sfsdf','sfsd',$content);
        }

        //PHP语法检查
        if(C('TMPL_DENY_PHP') && false != strpos($content,'<?php')){
               E(L('_NOT_ALLOW_PHP_'));
         }
        return $content;
      }
