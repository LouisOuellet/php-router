<?php

//Declaring namespace
namespace LaswitchTech\phpRouter;

//Import Factory class into the global namespace
use Composer\Factory;

class phpRouter {

  protected $URI = null;
  protected $Routes = [];
  protected $RootPath = null;

  public function __construct() {
    if($this->URI == null){ $this->URI = $_SERVER['REQUEST_URI']; }
    if($this->URI == ''){ $this->URI = '/'; }
    if($this->RootPath == null){ $this->RootPath = dirname(\Composer\Factory::getComposerFile()); }
    if(!is_file($this->RootPath . '/.htaccess')){
      $htaccess = "<IfModule mod_rewrite.c>\n";
      $htaccess .= "  RewriteEngine On\n";
      $htaccess .= "  RewriteBase /\n";
      $htaccess .= "  RewriteCond %{REQUEST_FILENAME} !-d\n";
      $htaccess .= "  RewriteCond %{REQUEST_FILENAME} !-f\n";
      $htaccess .= "  RewriteRule ^(.+)$ index.php [QSA,L]\n";
      $htaccess .= "</IfModule>\n";
      file_put_contents($this->RootPath . '/.htaccess', $htaccess);
    }
  }

  public function add($route, $destination){
    if(!isset($this->Routes[$route]) && is_file($destination)){
      $this->Routes[$route] = $destination;
      return true;
    }
    return false;
  }

  public function get($route){
    if(isset($this->Routes[$route])){ return $this->Routes[$route]; }
    return $this->RootPath . '/View/404.php';
  }

  public function load($route = null){
    if($route == null) { $route = $this->URI; }
    if(isset($this->Routes[$route])){ require $this->Routes[$route]; }
    elseif($this->URI == '/' && is_file($this->RootPath . '/View/index.php')){ require $this->RootPath . '/View/index.php'; }
    else {
      http_response_code(404);
      if(isset($this->Routes['404'])){ require $this->Routes['404']; }
      elseif(is_file($this->RootPath . '/View/404.php')){ require $this->RootPath . '/View/404.php'; }
    }
  }
}
