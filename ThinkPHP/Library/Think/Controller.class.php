<?php
namespace Think;

abstract class Controller{

  protected $view  =  null;

  protected $config = array();

  public  function __construct(){
     Hook::listen('action_begin',$this->config);
     //实例化视图类
     $this->view = Think::instance('Think\View');
     //控制器初始化
     if(method_exists($this,'_initialize'))
          $this->_initialize();
   }

   //模板显示 调用内置对模板您引擎显示方法
   protected function display($templateFile='',$charset='',$contentType='',$content='',$prefix){
       $this->view->display($templateFile,$charset,$contentType,$content,$prefix);
    }

   //输出内容文本可以包括HTML  并支持内容解析
   protected function show($content,$charset='',$contentType='',$prefix=''){
      $this->view->display('',$charset,$contentType,$content,$prefix);
   }

   //获取输出页面对内容
   protected function fetch($templateFile='',$content='',$prefix=''){
          return $this->view->fetch($templateFile,$content,$prefix);
   }

   //创建静态页面
   protected function buildHtml($htmlfile='',$htmlpath='',$templateFile=''){
      $content = $this->fetch($templateFile);
      $htmlpath= !empty($htmlpath)?$htmlpath:HTML_PATH;
      $htmlfile= $htmlpath.$htmlfile.C('HTML_FILE_SUFFIX');
      Storage::put($htmlfile,$content,'html');
      return $content;
   }

   //模板主题设置
   protected function theme($theme){
      $this->view->theme($theme);
      return $this;
   }

   //模板变量赋值
   protected function assign($name,$value=''){
     $this->view->assign($name,$value);
     return $this;
   }

   public function __set($name,$value){
      $this->assign($name,$value);
   }

   //取得模板显示变量对值
   public function get($name=''){
       return $this->view->get($name);
   }

   public function __get($name){
       return $this->get($name);
   }

   //检测模板变量的值
   public function __isset($name){
      return $this->get($name);
   }

   public function __call($method,$args){
     if(0 === strcasecmp($method,ACTION_NAME.C('ACTION_SUFFIX'))){
          if(method_exists($this,'_empty')){
               $this->_empty($method,$args);
          }elseif(file_exists_case($this->view->parseTemplate())){
              $this->display();
          }else{
              E(L('_ERROR_ACTION_').':'.ACTION_NAME);
          }
       }else{
              E(__CLASS__.':'.$method.L('_METHOD_NOT_EXIST_'));
              return;
       }
    }

    //操作错误跳转对快捷方法 
    protected function error($message='',$jumpUrl='',$ajax=false){
        $this->dispatchJump($message,0,$jumpUrl,$ajax);
    }

    //操作成功跳转对快捷方法
    protected function success($message='',$jumpUrl='',$ajax=false){
        $this->dispatchJump($message,1,$jumpUrl,$ajax);
    }

    //Ajax方式返回数据到客户端
    protected function ajaxReturn($data,$type=''){
       if(empty($type)) $type = C('DEFALT_AJAX_RETURN');
       switch(strtoupper($type)){
           case 'JSON':
               header('Content-Type:application/json;charset=utf-8');
               exit(json_encode($data));
           case 'XML':
               header('Content-Type:text/xml;charset=utf-8');
               exit(xml_encode($data));
           case 'JSONP':
               header('Content-Type:application/json=utf-8');
               $handler = isset($_GET[C('VAR_JSONP_HANDLER')])?$_GET[C('VAR_JSONP_HANDLER')]:C('DEFAULT_JSONP_HANDLER');
               exit($handler.'('.json_encode($data).');');
            case 'EVAL':
               header('COntent-Type:text/html;charset=utf-8');
               exit($data);
            default:
               //用于扩展其他返回格式数据
               Hook::listen('ajax_return',$data);
          }
        }

        //Action跳转（URL重定向） 支持制定模块和延时跳转
        protected function redirect($url,$params=array(),$delay=0,$msg=''){
            $url = U($url,$params);
            redirect($url,$delay,$msg);
        }

        //默认跳转操作 支持错误导向和正确跳转
        private function dispatchJump($message,$status=1,$jumpUrl='',$ajax=false){
           if(true === $ajax || IS_AJAX){
               $data = is_array($ajax)?$ajax:array();
               $data['info'] = $message;
               $data['status'] = $status;
               $data['url'] = $jumpUrl;
               $this->ajaxReturn($data);
           }
            if(is_int($ajax)) $this->assign('waitSecond',$ajax);
            if(!empty($jumpUrl)) $this->assign('jumpUrl',$jumpUrl);
            //提示标题
            $this->assign('msgTitle',$status?L('_OPERATION_SUCCESS_'):L('_OPERATION_FAIL_'));
             //如果设置了关闭窗口,则提示完毕后自动关闭窗口
             if($this->get('closeWin')) $this->assign('jumpUrl','javascript:window.close();');
             $this->assign('status',$status);
             //保证输出不受静态缓存影响
            C('HTML_CACHE_ON',false);
            if($status){
                $this->assgin('message',$message);
                if(!isset($this->waitSecond))  $this->assign('waitSecond','1');
                if(!isset($this->jumpUrl)) $this->assign('jumpUrl',$_SERVER['HTTP_REFERER']);
                 $this->display(C('TMPL_ACTION_SUCCESS'));
            }else{
                $this->assign('error',$message);
                if(!isset($this->waitSecond))  $this->assign('waitSecond','3');
                if(!isset($this->jumpUrl)) $this->assign('jumpUrl','javascript:history.back(-1_;');
                $this->display(C('TMPL_ACTION_ERROR'));
                exit;
             }
        }

       public function __destruct(){
           Hook::listen('action_end');
       }
}

//设置控制器别名 便于升级
class_alias('Think\Controller','Think\Action');
