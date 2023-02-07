<?php

//Import phpRouter class into the global namespace
use LaswitchTech\phpRouter\phpRouter;

define('ROUTER_ROOT',__DIR__);

if(!is_file(__DIR__ . '/webroot/index.php')){

  //Load Composer's autoloader
  require ROUTER_ROOT . "/vendor/autoload.php";

  //Initiate phpRouter
  $phpRouter = new phpRouter();
}

require __DIR__ . '/webroot/index.php';
