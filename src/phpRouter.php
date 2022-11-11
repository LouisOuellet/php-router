<?php

//Declaring namespace
namespace LaswitchTech\phpRouter;

//Import Factory class into the global namespace
use Composer\Factory;

class phpRouter {

  protected $URI = null;
  protected $Route = null;
  protected $Routes = [];
  protected $Path = null;
  protected $View = null;
  protected $Template = null;

  public function __construct() {
    if($this->URI == null){ $this->URI = $_SERVER['REQUEST_URI']; }
    if($this->URI == ''){ $this->URI = '/'; }
    if($this->Path == null){ $this->Path = dirname(\Composer\Factory::getComposerFile()); }
    if(!is_file($this->Path . '/.htaccess')){
      $htaccess = "<IfModule mod_rewrite.c>\n";
      $htaccess .= "  RewriteEngine On\n";
      $htaccess .= "  RewriteBase /\n";
      $htaccess .= "  RewriteCond %{REQUEST_FILENAME} !-d\n";
      $htaccess .= "  RewriteCond %{REQUEST_FILENAME} !-f\n";
      $htaccess .= "  RewriteRule ^(.+)$ index.php [QSA,L]\n";
      $htaccess .= "</IfModule>\n";
      file_put_contents($this->Path . '/.htaccess', $htaccess);
    }
    $this->add('404','View/404.php');
    if(defined('ROUTER_ROUTES')){
      $routes = ROUTER_ROUTES;
      if(is_array($routes)){
        foreach($routes as $route => $param){
          if(isset($param['view'])){ $view = $param['view']; } else { $view = null; }
          if(isset($param['template'])){ $template = $param['template']; } else { $template = null; }
          $this->add($route, $view, $template);
        }
      }
    }
    $this->load();
  }

  public function getURI(){ return $this->URI; }

  public function getView(){ return $this->View; }

  public function getTemplate(){ return $this->Template; }

  public function add($route, $view, $template = null){
    if(is_file($view) && (is_file($template) || $template == null)){
      $this->Routes[$route] = [ "view" => $view, "template" => $template ];
      return true;
    }
    return false;
  }

  public function load($route = null){
    if($route == null) { $route = $this->URI; }
    if(isset($this->Routes[$route])){
      $this->Route = $route;
      $this->View = $this->Routes[$route]['view'];
      $this->Template = $this->Routes[$route]['template'];
      return true;
    } else {
      $this->Route = '404';
      $this->View = $this->Routes['404']['view'];
      $this->Template = $this->Routes['404']['template'];
    }
    return false;
  }

  public function render(){
    if(!isset($this->Routes[$this->Route]) || $this->Route == '404'){ http_response_code(404); }
    if($this->Template != null){ require $this->Template; return $this->Template; }
    if($this->View != null){ require $this->View; return $this->View; }
  }
}
