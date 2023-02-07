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

    // Identifying Project Root
    if($this->Path == null){
      if(defined('ROUTER_ROOT')){
        $this->Path = ROUTER_ROOT;
      } else {
        $this->Path = dirname(__DIR__);
      }
    }
    if(!defined('ROUTER_ROOT')){
      define('ROUTER_ROOT',$this->Path);
    }

    // Check Requirements
    $this->checkRequirements();

    // Setup Webroot
    $this->genWebroot();

    // Configuring Router
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

  protected function scandir($directory, $filter = "ANY"){
    if(!str_starts_with($directory,'/')){ $directory = '/' . $directory; }
    $path = $this->Path . $directory;
    if(!str_ends_with($path,'/')){ $path .= '/'; }
    $files = [];
    foreach(scandir($path) as $file){
      if($filter){
        switch(strtoupper($filter)){
          case"DIRECTORY":
          case"DIRECTORIES":
          case"DIR":
            if(is_dir($path.$file) && !in_array($file,['.','..'])){
              $files[] = $file;
            }
            break;
          case"FILES":
          case"FILE":
            if(is_file($path.$file) && !in_array($file,['.DS_Store'])){
              $files[] = $file;
            }
            break;
          case"ALL":
          case"ANY":
            if((is_file($path.$file) && !in_array($file,['.DS_Store'])) || (is_dir($path.$file) && !in_array($file,['.','..']))){
              $files[] = $file;
            }
            break;
        }
      } else {
        $files[] = $file;
      }
    }
    return $files;
  }

  protected function getIndex(){
    $index = '';
    $index .= '<?php' . PHP_EOL;
    $index .= PHP_EOL;
    $index .= '//Initiate Session' . PHP_EOL;
    $index .= 'session_start();' . PHP_EOL;
    $index .= PHP_EOL;
    $index .= '//Import phpRouter class into the global namespace' . PHP_EOL;
    $index .= 'use LaswitchTech\phpRouter\phpRouter;' . PHP_EOL;
    $index .= PHP_EOL;
    $index .= 'if(!defined("ROUTER_ROOT")){' . PHP_EOL;
    $index .= '  define("ROUTER_ROOT",dirname(__DIR__));' . PHP_EOL;
    $index .= '}' . PHP_EOL;
    $index .= PHP_EOL;
    $index .= '//Load Composer\'s autoloader' . PHP_EOL;
    $index .= 'require ROUTER_ROOT . "/vendor/autoload.php";' . PHP_EOL;
    $index .= PHP_EOL;
    $index .= '//Initiate phpRouter' . PHP_EOL;
    $index .= '$phpRouter = new phpRouter();' . PHP_EOL;
    $index .= PHP_EOL;
    $index .= '//Render Request' . PHP_EOL;
    $index .= '$phpRouter->render();' . PHP_EOL;

    return $index;

    // //Defining Routes
    // define("ROUTER_ROUTES",[
    //   "404" => ["view" => "View/404.php", "label" => "404 - Not Found"],
    //   "/" => ["view" => "View/index.php", "template" => "Template/index.php", "public" => false, "error" => "/signin"],
    //   "/signin" => ["view" => "View/signin.php"],
    //   "/info" => ["view" => "View/info.php"],
    //   "/install" => ["view" => "View/install.php"],
    // ]);
    //
    // //Defining Requirements
    // define("ROUTER_REQUIREMENTS", ["APACHE" => ["mod_rewrite"]]);
  }

  protected function genIndex($webroot){
    $file = $webroot . '/index.php';
    if(!is_file($file)){
      file_put_contents($file, $this->getIndex());
    }
  }

  protected function genWebroot(){
    $webroot = $this->mkdir('webroot');
    foreach($this->scandir('dist','directory') as $directory){
      if(!str_starts_with($directory,'/')){ $directory = '/' . $directory; }
      $link = $webroot.$directory;
      $target = $this->Path.'/dist'.$directory;
      if(is_dir($target) && !is_dir($link) && !is_file($link)){
        symlink($target, $link);
      }
    }
    $this->genHTAccess();
    $this->genIndex();
  }

  protected function genHTAccess(){
    if(!is_file($this->Path . '/.htaccess')){
      $htaccess = '';
      $file = $this->Path . '/View/500.php';
      if(is_file($file)){
        $htaccess .= 'ErrorDocument 500 "' . $file . '"';
        $htaccess .= PHP_EOL;
      }
      $file = $this->Path . '/View/404.php';
      if(is_file($file)){
        $htaccess .= 'ErrorDocument 404 "' . $file . '"';
        $htaccess .= PHP_EOL;
      }
      $file = $this->Path . '/View/403.php';
      if(is_file($file)){
        $htaccess .= 'ErrorDocument 403 "' . $file . '"';
        $htaccess .= PHP_EOL;
      }
      $htaccess .= "Options All -Indexes" . PHP_EOL;
      $htaccess .= PHP_EOL;
      $htaccess .= "<IfModule mod_headers.c>" . PHP_EOL;
      $htaccess .= "  RequestHeader unset Proxy" . PHP_EOL;
      $htaccess .= "</IfModule>" . PHP_EOL;
      $htaccess .= PHP_EOL;
      $htaccess .= "<IfModule mod_rewrite.c>" . PHP_EOL;
      $htaccess .= "  RewriteEngine on" . PHP_EOL;
      $htaccess .= "  RewriteRule ^(\.well-known/.*)$ $1 [L]" . PHP_EOL;
      $htaccess .= "  RewriteRule ^$ webroot/ [L]" . PHP_EOL;
      $htaccess .= "  RewriteRule (.*) webroot/$1 [L]" . PHP_EOL;
      $htaccess .= "</IfModule>" . PHP_EOL;

      file_put_contents($this->Path . '/.htaccess', $htaccess);
    }

    if(!is_file($this->Path . '/webroot/.htaccess')){
      $htaccess = '';
      $file = $this->Path . '/View/500.php';
      if(is_file($file)){
        $htaccess .= 'ErrorDocument 500 "' . $file . '"';
        $htaccess .= PHP_EOL;
      }
      $file = $this->Path . '/View/404.php';
      if(is_file($file)){
        $htaccess .= 'ErrorDocument 404 "' . $file . '"';
        $htaccess .= PHP_EOL;
      }
      $file = $this->Path . '/View/403.php';
      if(is_file($file)){
        $htaccess .= 'ErrorDocument 403 "' . $file . '"';
        $htaccess .= PHP_EOL;
      }
      $htaccess .= "Options All -Indexes" . PHP_EOL;
      $htaccess .= PHP_EOL;
      $htaccess .= "<IfModule mod_headers.c>" . PHP_EOL;
      $htaccess .= "  RequestHeader unset Proxy" . PHP_EOL;
      $htaccess .= "</IfModule>\n";
      $htaccess .= PHP_EOL;
      $htaccess .= "<IfModule mod_rewrite.c>" . PHP_EOL;
      $htaccess .= "  RewriteEngine On" . PHP_EOL;
      $htaccess .= "  RewriteBase /" . PHP_EOL;
      $htaccess .= "  RewriteCond %{REQUEST_FILENAME} !-d" . PHP_EOL;
      $htaccess .= "  RewriteCond %{REQUEST_FILENAME} !-f" . PHP_EOL;
      $htaccess .= "  RewriteRule ^(.+)$ index.php [QSA,L]" . PHP_EOL;
      $htaccess .= "  RewriteRule ^config/.*$ - [F,L]" . PHP_EOL;
      $htaccess .= "  RewriteRule ^tmp/.*$ - [F,L]" . PHP_EOL;
      $htaccess .= "  RewriteRule ^data/.*$ - [F,L]" . PHP_EOL;
      $htaccess .= "  RewriteRule ^cli - [F,L]" . PHP_EOL;
      $htaccess .= "  RewriteRule ^.htaccess - [F,L]" . PHP_EOL;
      $htaccess .= "</IfModule>" . PHP_EOL;

      file_put_contents($this->Path . '/webroot/.htaccess', $htaccess);
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
                    // $this->sendOutput('This application requires a '.strtoupper($server).' server with ' . $module . ' module enabled.', array('HTTP/1.1 500 Internal Error'));
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
    if($view != null){
      $view = ROUTER_ROOT . '/' . $view;
    }
    if($template != null){
      $template = ROUTER_ROOT . '/' . $template;
    }
    if($view != null && is_file($view) && ($template == null || is_file($template))){
      $this->Routes[$route] = [ "view" => $view, "template" => $template, "label" => $label, "public" => $public, "error" => $error ];
      return true;
    }
    echo "Route: " . json_encode($route,JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    echo "View: " . json_encode($view,JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    echo "Template: " . json_encode($template,JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    echo "Test: " . json_encode($view != null,JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    echo "Test: " . json_encode(is_file($view) != null,JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    echo "Test: " . json_encode(($template == null || is_file($template)) != null,JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
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
      if(isset($this->Routes['404']['view'])){ $this->View = $this->Routes['404']['view']; }
      else { $this->View = null; }
      if(isset($this->Routes['404']['template'])){ $this->Template = $this->Routes['404']['template']; }
      else { $this->Template = null; }
      if(isset($this->Routes['404']['label'])){ $this->Label = $this->Routes['404']['label']; }
      else { $this->Label = null; }
    }
    return false;
  }

  public function render(){
    // echo json_encode($this->Routes,JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if(!isset($this->Routes[$this->Route]) || $this->Route == '404'){ http_response_code(404); }
    if($this->Template != null){ require $this->Template; return $this->Template; }
    if($this->View != null){ require $this->View; return $this->View; }
  }
}
