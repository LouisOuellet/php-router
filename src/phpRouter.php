<?php

//Declaring namespace
namespace LaswitchTech\phpRouter;

//Import Factory class into the global namespace
use Composer\Factory;

class phpRouter {

  protected $URI = null;
  protected $Vars = null;
  protected $Route = null;
  protected $Routes = [];
  protected $Path = null;
  protected $View = null;
  protected $Label = null;
  protected $Template = null;
  protected $Requirements = ["SERVER" => "APACHE","MODULES" => ["APACHE" => ["mod_rewrite"]]];

  public function __construct(){

    // Configuring Router
    $this->checkRequirements();
    $this->genHTAccess();
    if($this->URI == null){ $this->URI = $_SERVER['REQUEST_URI']; }
    if($this->URI == ''){ $this->URI = '/'; }
    $this->URI = explode('?',$this->URI)[0];
    $this->add('404','View/404.php');
    if(defined('ROUTER_ROUTES')){
      $routes = ROUTER_ROUTES;
      if(is_array($routes)){
        foreach($routes as $route => $param){
          if(isset($param['view'])){ $view = $param['view']; } else { $view = null; }
          if(isset($param['template'])){ $template = $param['template']; } else { $template = null; }
          if(isset($param['public'])){ $public = $param['public']; } else { $public = true; }
          if(isset($param['error'])){ $error = $param['error']; } else { $error = null; }
          if(isset($param['label'])){ $label = $param['label']; } else { $label = null; }
          $this->add($route, $view, $template, $label, $public, $error);
        }
      }
    }
    $this->load();
  }

  public function __call($name, $arguments) {
    return [ "error" => "[".$name."] 501 Not Implemented" ];
  }

  protected function configure($array = []){
    try {
      $config = [];
      $this->mkdir('config');
      if(is_file($this->Path . '/config/config.json')){
        $config = json_decode(file_get_contents($this->Path . '/config/config.json'),true);
      }
      foreach($array as $key => $value){ $config[$key] = $value; }
      $json = fopen($this->Path . '/config/config.json', 'w');
      fwrite($json, json_encode($config, JSON_PRETTY_PRINT));
      fclose($json);
      return true;
    } catch(Exception $error){
      return false;
    }
  }

  protected function configurations($key = null){
    $config = [];
    if(is_file($this->Path . '/config/config.json')){
      $config = json_decode(file_get_contents($this->Path . '/config/config.json'),true);
    }
    if($key != null){
      if(isset($config[$key])){ return $config[$key]; }
      return null;
    }
    return $config;
  }

  protected function mkdir($directory){
    $make = $this->Path;
    $directories = explode('/',$directory);
    foreach($directories as $subdirectory){
      $make .= '/'.$subdirectory;
      if(!is_file($make)&&!is_dir($make)){ mkdir($make, 0777, true); }
    }
    return $make;
  }

  protected function genHTAccess(){
    if($this->Path == null){ $this->Path = dirname(\Composer\Factory::getComposerFile()); }
    if(!is_file($this->Path . '/.htaccess')){
      $htaccess = "Options All -Indexes\n";
      $htaccess .= "\n";
      $htaccess .= "<IfModule mod_rewrite.c>\n";
      $htaccess .= "  RewriteEngine On\n";
      $htaccess .= "  RewriteBase /\n";
      $htaccess .= "  RewriteCond %{REQUEST_FILENAME} !-d\n";
      $htaccess .= "  RewriteCond %{REQUEST_FILENAME} !-f\n";
      $htaccess .= "  RewriteRule ^(.+)$ index.php [QSA,L]\n";
      $htaccess .= "  RewriteRule ^config/.*$ - [F,L]\n";
      $htaccess .= "  RewriteRule ^tmp/.*$ - [F,L]\n";
      $htaccess .= "  RewriteRule ^cli - [F,L]\n";
      $htaccess .= "  RewriteRule ^.htaccess - [F,L]\n";
      $htaccess .= "</IfModule>\n";
      file_put_contents($this->Path . '/.htaccess', $htaccess);
    }
  }

  protected function checkRequirements(){
    if(defined('ROUTER_REQUIREMENTS') && is_array(ROUTER_REQUIREMENTS)){
      foreach(ROUTER_REQUIREMENTS as $type => $modules){
        foreach($modules as $module){
          if(!isset($this->Requirements["MODULES"][$type])){ $this->Requirements["MODULES"][$type] = []; }
          if(!in_array($module,$this->Requirements["MODULES"][$type])){ $this->Requirements["MODULES"][$type][] = $module; }
        }
      }
    }
    foreach($this->Requirements as $type => $requirement){
      switch(strtoupper($type)){
        case"MODULES":
          foreach($requirement as $server => $modules){
            foreach($modules as $module){
              switch(strtoupper($server)){
                case"APACHE":
                  if(function_exists('apache_get_modules')){
                    if(!in_array(strtolower($module),apache_get_modules())){
                      $this->sendOutput('This application requires the '.strtoupper($server).' module: '.strtolower($module).'.', array('HTTP/1.1 500 Internal Error'));
                    }
                  } else {
                    $this->sendOutput('This application requires a '.strtoupper($requirement).' server.', array('HTTP/1.1 500 Internal Error'));
                  }
                  break;
                case"PHP":
                  if(!in_array(get_loaded_extensions(strtolower($module)))){
                    $this->sendOutput('This application requires the '.strtoupper($server).' module: '.strtolower($module).'.', array('HTTP/1.1 500 Internal Error'));
                  }
                  break;
              }
            }
          }
          break;
        case"SERVER":
          if(strtoupper($requirement) == "APACHE" && strpos($_SERVER['SERVER_SOFTWARE'], 'Apache') === false){
            $this->sendOutput('This application requires a '.strtoupper($requirement).' server.', array('HTTP/1.1 500 Internal Error'));
          }
          break;
      }
    }
  }

  protected function sendOutput($data, $httpHeaders=array()) {
    header_remove('Set-Cookie');
    if (is_array($httpHeaders) && count($httpHeaders)) {
      foreach ($httpHeaders as $httpHeader) {
        header($httpHeader);
      }
    }
    echo $data;
    exit;
  }

  public function getURI(){ return $this->URI; }

  public function getRoute(){ return $this->Route; }

  public function getLabel(){ return $this->Label; }

  public function getRoutes(){ return array_keys($this->Routes); }

  public function getView(){ require $this->View;return $this->View; }

  public function getTemplate(){ return $this->Template; }

  public function isConnected(){
    if(isset($_SESSION) && !empty($_SESSION)){
      $array = $_SESSION;
      if(isset($array['csrf'])){ unset($array['csrf']); }
      return !empty($array);
    }
    return false;
  }

  public function parseURI(){
    if($this->Vars == null){
      if(count(explode('?',$_SERVER['REQUEST_URI'])) > 1){
        $vars = explode('?',$_SERVER['REQUEST_URI'])[1];
        $this->Vars = [];
        foreach(explode('&',$vars) as $var){
          $params = explode('=',$var);
          if(count($params) > 1){ $this->Vars[$params[0]] = $params[1]; }
          else { $this->Vars[$params[0]] = true; }
        }
      }
    }
    return $this->Vars;
  }

  protected function add($route, $view, $template = null, $label = null, $public = true, $error = null){
    if($view != null && is_file($view) && ($template == null || is_file($template))){
      $this->Routes[$route] = [ "view" => $view, "template" => $template, "label" => $label, "public" => $public, "error" => $error ];
      return true;
    }
    return false;
  }

  public function load($route = null){
    if($route == null) { $route = $this->URI; }
    if(isset($this->Routes[$route])){
      if($this->Routes[$route]['error'] != null){
        if($this->Routes[$route]['public'] && $this->isConnected()){
          $route = $this->Routes[$route]['error'];
        }
        if(!$this->Routes[$route]['public'] && !$this->isConnected()){
          $route = $this->Routes[$route]['error'];
        }
      }
    } else {
      $route = '404';
    }
    return $this->set($route);
  }

  protected function set($route){
    if($route == null) { $route = $this->URI; }
    if(isset($this->Routes[$route])){
      $this->Route = $route;
      $this->View = $this->Routes[$this->Route]['view'];
      $this->Template = $this->Routes[$this->Route]['template'];
      $this->Label = $this->Routes[$this->Route]['label'];
      return true;
    } else {
      $this->Route = '404';
      $this->View = $this->Routes['404']['view'];
      $this->Template = $this->Routes['404']['template'];
      $this->Label = $this->Routes['404']['label'];
    }
    return false;
  }

  public function render(){
    if(!isset($this->Routes[$this->Route]) || $this->Route == '404'){ http_response_code(404); }
    if($this->Template != null){ require $this->Template; return $this->Template; }
    if($this->View != null){ require $this->View; return $this->View; }
  }
}
