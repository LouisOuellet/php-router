<?php

//Declaring namespace
namespace LaswitchTech\phpRouter;

// Import phpConfigurator class into the global namespace
use LaswitchTech\phpConfigurator\phpConfigurator;

// Import phpLogger class into the global namespace
use LaswitchTech\phpLogger\phpLogger;

// Import phpAUTH Class into the global namespace
use LaswitchTech\phpAUTH\phpAUTH;

// Import phpCSRF Class into the global namespace
use LaswitchTech\phpCSRF\phpCSRF;

//Import Exception class into the global namespace
use \Exception;

class phpRouter {

  // Constants
  const HttpCodes = [400,401,403,404,422,423,427,428,429,430,432,500,501];
  const HttpCustomCodes = [427,430,432];
  const HttpLabels = [
    "400" => "Bad Request", // 400 Error Document // Bad Request
    "401" => "Unauthorized", // 401 Error Document // Unauthorized
    "403" => "Forbidden", // 403 Error Document // Forbidden
    "404" => "Not Found", // 404 Error Document // Not Found
    "422" => "Unprocessable Content", // 422 Error Document // Unprocessable Content
    "423" => "Locked", // 423 Error Document // Locked
    "427" => "2FA Required", // 427 Error Document // 2FA Required
    "428" => "Verification Required", // 428 Error Document // Verification Required
    "429" => "Too Many Requests", // 429 Error Document // Too Many Requests
    "430" => "Unauthenticated", // 430 Error Document // Unauthenticated
    "432" => "Unverified", // 432 Error Document // Unverified
    "500" => "Internal Server Error", // 500 Error Document // Internal Server Error
    "501" => "Not Implemented", // 501 Error Document // Not Implemented
  ];

	// phpLogger
	protected $Logger = null;

  // phpConfigurator
  protected $Configurator = null;

  // phpAUTH
  protected $Auth = null;

  // phpCSRF
  protected $CSRF = null;

  // Properties
  protected $Namespace = null;
  protected $Defaults = [
    "view" => null,
    "template" => null,
    "label" => null,
    "icon" => null,
    "public" => true,
    "permission" => false,
    "level" => 1,
    "error" => [
      "400" => null, // 400 Error Document // Bad Request
      "401" => null, // 401 Error Document // Unauthorized
      "403" => null, // 403 Error Document // Forbidden
      "404" => null, // 404 Error Document // Not Found
      "422" => null, // 422 Error Document // Unprocessable Content
      "423" => null, // 423 Error Document // Locked
      "427" => null, // 427 Error Document // 2FA Required
      "428" => null, // 428 Error Document // Verification Required
      "429" => null, // 429 Error Document // Too Many Requests
      "430" => null, // 430 Error Document // Unauthenticated
      "432" => null, // 432 Error Document // Unverified
      "500" => null, // 500 Error Document // Internal Server Error
      "501" => null, // 501 Error Document // Not Implemented
    ],
  ];
  protected $URI = null;
  protected $Vars = null;
  protected $Route = null;
  protected $Routes = [];
  protected $View = null;
  protected $Label = null;
  protected $Icon = null;
  protected $Template = null;

  public function __construct(){

    // Initialize Configurator
    $this->Configurator = new phpConfigurator(['routes','requirements']);

    // Initiate phpLogger
    $this->Logger = new phpLogger('router');

    // Initiate phpAuth
    $this->Auth = new phpAUTH();

    // Initiate phpCSRF
    $this->CSRF = new phpCSRF();

    // Check Requirements
    $this->checkRequirements();

    // Setup Webroot
    $this->genWebroot();

    // Load Routes
    $this->loadRoutes();

    // Parse URI
    $this->parseURI();

    // Load Route
    $this->load();
  }

  public function __call($name, $arguments) {
    return [ "error" => "[".$name."] 501 Not Implemented" ];
  }
  
  public function config($option, $value){
		try {
			if(is_string($option)){
	      switch($option){
	        case"hostnames":
	          if(is_array($value)){

							// Save to Configurator
							$this->Configurator->set('auth',$option, $value);
	          } else{
	            throw new Exception("2nd argument must be an array.");
	          }
	          break;
	        default:
	          throw new Exception("unable to configure $option.");
	          break;
	      }
	    } else{
	      throw new Exception("1st argument must be as string.");
	    }
		} catch (Exception $e) {

			// If an exception is caught, log an error message
			$this->Logger->error('Error: '.$e->getMessage());
		}

    return $this;
  }

  protected function checkRequirements(){

    // Check Server Requirements
    if($this->Configurator->get('requirements','server')){
      if(strtoupper($this->Configurator->get('requirements','server')) == "APACHE" && strpos($_SERVER['SERVER_SOFTWARE'], 'Apache') === false){
        $this->sendOutput(
          'This application requires a '.strtoupper($this->Configurator->get('requirements','server')).' server.',
          array('HTTP/1.1 500 Internal Error'),
        );
      }
    }

    // Check PHP Module Requirements
    if($this->Configurator->get('requirements','php')){
      foreach($this->Configurator->get('requirements','php') as $module){
        if(!in_array(get_loaded_extensions(strtolower($module)))){
          $this->sendOutput(
            'This application requires the '.strtoupper($module).' module: '.strtolower($module).'.',
            array('HTTP/1.1 500 Internal Error'),
          );
        }
      }
    }

    // Check Apache Module Requirements
    if($this->Configurator->get('requirements','apache')){
      foreach($this->Configurator->get('requirements','apache') as $module){
        if(function_exists('apache_get_modules')){
          if(!in_array(strtolower($module),apache_get_modules())){
            $this->sendOutput(
              'This application requires the '.strtoupper($module).' module: '.strtolower($module).'.',
              array('HTTP/1.1 500 Internal Error'),
            );
          }
        } else {
          $this->sendOutput('This application requires a '.strtoupper($server).' server with ' . $module . ' module enabled.', array('HTTP/1.1 500 Internal Error'));
        }
      }
    }
  }

  protected function genWebroot(){

    // Create Webroot
    if(!is_dir($this->Configurator->root() . '/webroot')){
      mkdir($this->Configurator->root() . '/webroot', 0755, true);
    }

    // Create Webroot Symlinks
    $directories = $this->scandir('dist','directory');
    foreach($directories as $directory){
      if(!str_starts_with($directory,'/')){ $directory = '/' . $directory; }
      $link = $this->Configurator->root() . '/webroot' . $directory;
      $target = $this->Configurator->root() . '/dist'.$directory;
      if(is_dir($target) && !is_dir($link) && !is_file($link)){
        chmod($target, 0755);
        symlink($target, $link);
      }
    }

    // Create Webroot API Symlinks
    $link = $this->Configurator->root() . '/webroot'.'/api.php';
    $target = $this->Configurator->root() . '/api.php';
    if(!is_file($link) && is_file($target)){
      chmod($target, 0755);
      symlink($target, $link);
    }

    // Create .htaccess files
    $this->genHTAccess();

    // Create Webroot index.php
    $this->genIndex();
  }

  protected function genHTAccess(){

    // Generate List of Error Documents
    $errors = '';

    // Add Error Documents
    foreach(self::HttpCodes as $Code){
      if(in_array($Code,self::HttpCustomCodes)){
        continue;
      }
      $file = $this->Configurator->root() . '/View/'.$Code.'.php';
      if(is_file($file)){
        $errors .= 'ErrorDocument '.$Code.' "' . $file . '"' . PHP_EOL;
      }
    }
    if($errors != ''){
      $errors .= PHP_EOL;
    }

    // Create root .htaccess if it doesn't exist
    if(!is_file($this->Configurator->root() . '/.htaccess')){

      // Initialize .htaccess
      $htaccess = $errors;

      // Apache Options
      $htaccess .= "Options All -Indexes" . PHP_EOL;
      $htaccess .= "Options +FollowSymLinks" . PHP_EOL;
      $htaccess .= "Options +SymLinksIfOwnerMatch" . PHP_EOL;
      $htaccess .= PHP_EOL;

      // Apache Headers
      $htaccess .= "<IfModule mod_headers.c>" . PHP_EOL;
      $htaccess .= "  RequestHeader unset Proxy" . PHP_EOL;
      $htaccess .= "</IfModule>" . PHP_EOL;
      $htaccess .= PHP_EOL;

      // Apache Rewrite Engine
      $htaccess .= "<IfModule mod_rewrite.c>" . PHP_EOL;
      $htaccess .= "  RewriteEngine on" . PHP_EOL;
      $htaccess .= "  RewriteRule ^(\.well-known/.*)$ $1 [L]" . PHP_EOL;
      $htaccess .= "  RewriteRule ^$ webroot/ [L]" . PHP_EOL;
      $htaccess .= "  RewriteRule (.*) webroot/$1 [L]" . PHP_EOL;
      $htaccess .= "</IfModule>" . PHP_EOL;
      $htaccess .= PHP_EOL;

      file_put_contents($this->Configurator->root() . '/.htaccess', trim($htaccess));
    }

    // Create webroot .htaccess if it doesn't exist
    if(!is_file($this->Configurator->root() . '/webroot/.htaccess')){

      // Initialize .htaccess
      $htaccess = $errors;

      // Apache Options
      $htaccess .= "Options All -Indexes" . PHP_EOL;
      $htaccess .= "Options +FollowSymLinks" . PHP_EOL;
      $htaccess .= "Options +SymLinksIfOwnerMatch" . PHP_EOL;
      $htaccess .= PHP_EOL;

      // Apache Headers
      $htaccess .= "<IfModule mod_headers.c>" . PHP_EOL;
      $htaccess .= "  RequestHeader unset Proxy" . PHP_EOL;
      $htaccess .= "</IfModule>" . PHP_EOL;
      $htaccess .= PHP_EOL;

      // Apache Rewrite Engine
      $htaccess .= "<IfModule mod_rewrite.c>" . PHP_EOL;
      $htaccess .= "  RewriteEngine On" . PHP_EOL;
      $htaccess .= "  RewriteBase /" . PHP_EOL;
      $htaccess .= "  RewriteCond %{REQUEST_FILENAME} !-d" . PHP_EOL;
      $htaccess .= "  RewriteCond %{REQUEST_FILENAME} !-f" . PHP_EOL;
      $htaccess .= "  RewriteRule ^(.+)$ index.php [QSA,L]" . PHP_EOL;
      $htaccess .= "  RewriteRule ^cli - [F,L]" . PHP_EOL;
      $htaccess .= "  RewriteRule ^.htaccess - [F,L]" . PHP_EOL;
      $htaccess .= "</IfModule>" . PHP_EOL;
      $htaccess .= PHP_EOL;

      file_put_contents($this->Configurator->root() . '/webroot/.htaccess', trim($htaccess));
    }
  }

  protected function genIndex(){

    // Create index.php if it doesn't exist
    $file = $this->Configurator->root() . '/webroot/index.php';
    if(!is_file($file)){
      $index = '';
      $index .= '<?php' . PHP_EOL . PHP_EOL;
      $index .= '// Initiate Session' . PHP_EOL;
      $index .= 'if(session_status() !== PHP_SESSION_ACTIVE){' . PHP_EOL;
      $index .= '  session_start();' . PHP_EOL;
      $index .= '}' . PHP_EOL;
      $index .= PHP_EOL;
      $index .= '// Import phpRouter class into the global namespace' . PHP_EOL;
      $index .= 'use LaswitchTech\phpRouter\phpRouter;' . PHP_EOL;
      $index .= PHP_EOL;
      $index .= '// Load Composer\'s autoloader' . PHP_EOL;
      $index .= 'require dirname(__DIR__) . "/vendor/autoload.php";' . PHP_EOL;
      $index .= PHP_EOL;
      $index .= '// Initiate phpRouter' . PHP_EOL;
      $index .= '$phpRouter = new phpRouter();' . PHP_EOL;
      $index .= PHP_EOL;
      $index .= '// Render Request' . PHP_EOL;
      $index .= '$phpRouter->render();' . PHP_EOL;
      file_put_contents($file, trim($index));
    }
  }

  protected function loadRoutes(){

    // Set Error Routes
    foreach(self::HttpCodes as $Code){
      $Code = strval($Code);
      $file = $this->Configurator->root() . '/View/'.$Code.'.php';
      if(is_file($file)){
        $this->Defaults['error'][$Code] = $Code;
      }
    }

    // Load Error Routes
    foreach(self::HttpCodes as $Code){
      $file = $this->Configurator->root() . '/View/'.$Code.'.php';
      if(is_file($file)){
        $this->add(strval($Code), '/View/'.$Code.'.php', ['label' => self::HttpLabels[$Code]]);
      }
    }

    // Load Routes
    if($this->Configurator->get('routes','routes')){
      foreach($this->Configurator->get('routes','routes') as $route => $param){

        // Set Route Parameters
        if(array_key_exists('view',$param)){
          $this->add(strval($route), $param['view'], $param);
        }
      }
    }
  }

  protected function add($route, $view, $options = []){

    // Set Defaults
    $defaults = $this->Defaults;

    // Set Options
    if(!is_array($options)){
      $options = [];
    }

    // Set Route Parameters
    foreach($options as $key => $value){
      if(array_key_exists($key,$defaults)){
        if(is_array($defaults[$key])){
          if(is_array($value)){
            foreach($value as $k => $v){
              if(array_key_exists($k,$defaults[$key])){
                $defaults[$key][$k] = $v;
              }
            }
          }
        } else {
          $defaults[$key] = $value;
        }
      }
    }

    // Set View
    $defaults['view'] = $view;

    // Add Route
    if($view != null && is_file($this->Configurator->root() . '/' . $view) && ($defaults['template'] == null || is_file($this->Configurator->root() . '/' . $defaults['template']))){

      // Set Route
      $this->Routes[strval($route)] = $defaults;

      // Return true
      return true;
    }

    // Return false
    return false;
  }

  protected function parseURI(){

    // Parse URI
    if($this->URI == null){ $this->URI = $_SERVER['REQUEST_URI']; }
    if($this->URI == ''){ $this->URI = '/'; }
    $this->URI = explode('?',$this->URI);

    // Parse Namespace
    if(is_array($this->URI)){
      $this->Namespace = $this->URI[0];
    } else {
      $this->Namespace = $this->URI;
    }

    // Parse Vars
    if(is_array($this->URI) && count($this->URI) > 1){
      $vars = $this->URI[1];
      $this->Vars = [];
      foreach(explode('&',$vars) as $var){
        $params = explode('=',$var);
        if(count($params) > 1){ $this->Vars[$params[0]] = $params[1]; }
        else { $this->Vars[$params[0]] = true; }
      }
    }
  }

  public function load($route = null){

    // Load Default Route
    if($route == null) { $route = $this->Namespace; }

    // Set Namespace as Route
    $namespace = $route;

    if(!array_key_exists($route,$this->Routes)){

      // Set Route as 404 - Not Found
      $namespace = '404';

      // Return
      return $this->set($namespace);
    }

    if(!$this->Routes[$route]['public'] && !$this->isAuthorized()){

      // Set Route as 401 - Unauthorized
      $namespace = '401';

      // Return
      return $this->set($namespace);
    }

    // Load Namespace
    if(!$this->Routes[$route]['public'] && !$this->isAuthenticated()){

      // Set Route as 430 - Unauthenticated
      $namespace = '430';

      // Check if 430 Error Document is set
      if(array_key_exists('430', $this->Routes[$route]['error']) && $this->Routes[$route]['error']['430'] !== null){
        $namespace = $this->Routes[$route]['error']['430'];
      }

      // If 2FA is enabled and the user is not authenticated
      // Set Route as 427 - 2FA Required
      if($this->Auth->Authentication->is2FAReady()){
        $namespace = '427';

        // Check if 427 Error Document is set
        if(array_key_exists('427', $this->Routes[$route]['error']) && $this->Routes[$route]['error']['427'] !== null){
          $namespace = $this->Routes[$route]['error']['427'];
        }
      }
    }
    if(!$this->Routes[$route]['public'] && $this->isAuthenticated()){
      
      // If User is verified
      if($this->Auth->Authentication->isVerified()){

        // Check if Route has permission
        if($this->Routes[$route]['permission'] && !$this->hasPermission("Route>" . $this->Namespace, $this->Routes[$route]['level'])){

          // Set Route as 403 - Forbidden
          $namespace = '403';
  
          // Check if 403 Error Document is set
          if(array_key_exists('403', $this->Routes[$route]['error']) && $this->Routes[$route]['error']['403'] !== null){
            $namespace = $this->Routes[$route]['error']['403'];
          }
        }
      } else {

        // Set Route as 432 - Email Not Verified
        $namespace = '432';

        // Check if 432 Error Document is set
        if(array_key_exists('432', $this->Routes[$route]['error']) && $this->Routes[$route]['error']['432'] !== null){
          $namespace = $this->Routes[$route]['error']['432'];
        }
      }
    }

    // Set Route
    return $this->set($namespace);
  }

  protected function set($route){

    // Load Default Route
    if($route == null) { $route = $this->Namespace; }

    // Load Route
    if(array_key_exists($route,$this->Routes)){

      // Set Route
      $this->Route = $route;
      $this->View = $this->Routes[$this->Route]['view'];
      $this->Template = $this->Routes[$this->Route]['template'];
      $this->Label = $this->Routes[$this->Route]['label'];
      $this->Icon = $this->Routes[$this->Route]['icon'];

      // Return true
      return true;
    }

    // Return false
    return false;
  }

  public function render(){

    // Render the corresponding view
    switch($this->Route){
      case'400':
      case'401':
      case'403':
      case'404':
      case'422':
      case'423':
      case'427':
      case'428':
      case'429':
      case'430':
      case'432':
      case'500':
      case'501':

        // Render the template if it is set
        if($this->Template !== null){ require $this->getTemplateFile(); return $this->Template; }

        // Render the view if it is set
        if($this->View !== null){ require $this->getViewFile(); return $this->View; }

        // Set the HTTP Response Code
        http_response_code(intval($this->Route));
        break;
      default:

        // Render the template if it is set
        if($this->Template !== null){ require $this->getTemplateFile(); return $this->Template; }

        // Render the view if it is set
        if($this->View !== null){ require $this->getViewFile(); return $this->View; }
        break;
    }

    // Internal Error
    http_response_code(500);
  }

  // Getters

  public function getURI(){ return $this->URI; }

  public function getNamespace(){ return $this->Namespace; }

  public function getVars(){ return $this->Vars; }

  public function getRoute(){ return $this->Route; }

  public function getLabel(){ return $this->Label; }

  public function getIcon(){ return $this->Icon; }

  public function getRoutes(){ return $this->Routes; }

  public function getView(){ return $this->View; }

  public function getViewFile(){ return $this->Configurator->root() . '/' . $this->View; }

  public function getTemplate(){ return $this->Template; }

  public function getTemplateFile(){
    return $this->Configurator->root() . '/' . $this->Template;
  }

  // Helper Methods

  protected function sendOutput($data, $httpHeaders=array()) {

    // Remove the default Set-Cookie header
    header_remove('Set-Cookie');

    // Add the custom headers
    if (is_array($httpHeaders) && count($httpHeaders)) {
      foreach ($httpHeaders as $httpHeader) {
        header($httpHeader);
      }
    }

    // Check if the data is an array or object
    if(is_array($data) || is_object($data)){

      // Convert the data to JSON
      $data = json_encode($data,JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }

    // Send the output
    echo $data;

    // Exit the script
    exit;
  }
  
  protected function scandir($directory, $filter = "ANY"){
    if(!str_starts_with($directory,'/')){ $directory = '/' . $directory; }
    $path = $this->Configurator->root() . $directory;
    if(!str_ends_with($path,'/')){ $path .= '/'; }
    $files = [];
    if(is_dir($path)){
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
    }
    return $files;
  }

  public function isAuthenticated(){

    // Return the authentication status
    return ($this->Auth->Authentication !== null && $this->Auth->Authentication->isAuthenticated());
  }

  protected function isAuthorized(){

    // Return the Authorization status
    return ($this->Auth->Authorization !== null && $this->Auth->Authorization->isAuthorized());
  }

  protected function hasPermission($permissionName, $requiredLevel = 1){

    // Return the permission status
    return ($this->Auth->Authorization !== null && $this->Auth->Authorization->hasPermission($permissionName, $requiredLevel = 1));
  }
}
