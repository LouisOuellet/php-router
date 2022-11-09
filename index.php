<?php

//Import API class into the global namespace
//These must be at the top of your script, not inside a function
use LaswitchTech\phpRouter\phpRouter;

//Load Composer's autoloader
require 'vendor/autoload.php';

//Initiate phpRouter
$phpRouter = new phpRouter();

//Add Routes
$phpRouter->add('/', __DIR__ . '/View/index.php');
$phpRouter->add('404', __DIR__ . '/View/404.php');
$phpRouter->add('403', __DIR__ . '/View/403.php');
$phpRouter->add('500', __DIR__ . '/View/500.php');
$phpRouter->add('/courses', __DIR__ . '/View/courses.php');
$phpRouter->add('/views/authors', __DIR__ . '/View/authors.php');
$phpRouter->add('/about', __DIR__ . '/View/aboutus.php');

//Load Request
$phpRouter->load();
