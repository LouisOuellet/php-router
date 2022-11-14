<?php

//Import API class into the global namespace
//These must be at the top of your script, not inside a function
use LaswitchTech\phpRouter\phpRouter;

//Load Composer's autoloader
require 'vendor/autoload.php';

//Defining Routes
define('ROUTER_ROUTES',[
  "/" => ["view" => "View/index.php"],
  "404" => ["view" => "View/404.php", "template" => "Template/index.php"],
  "/info" => ["view" => "View/info.php"],
]);

//Defining Requirements
define("ROUTER_REQUIREMENTS", ["APACHE" => ["mod_rewrite"]]);

//Initiate phpRouter
$phpRouter = new phpRouter();

//Render Request
$phpRouter->render();
