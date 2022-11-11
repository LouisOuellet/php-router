![GitHub repo logo](/dist/img/logo.png)

# phpRouter
![License](https://img.shields.io/github/license/LouisOuellet/php-router?style=for-the-badge)
![GitHub repo size](https://img.shields.io/github/repo-size/LouisOuellet/php-router?style=for-the-badge&logo=github)
![GitHub top language](https://img.shields.io/github/languages/top/LouisOuellet/php-router?style=for-the-badge)
![Version](https://img.shields.io/github/v/release/LouisOuellet/php-router?label=Version&style=for-the-badge)

## Features
 - Easy tu use PHP Router

## Why you might need it
If you are looking for an easy start for your PHP router. Then this PHP Class is for you.

## Can I use this?
Sure!

## License
This software is distributed under the [GNU General Public License v3.0](https://www.gnu.org/licenses/gpl-3.0.en.html) license. Please read [LICENSE](LICENSE) for information on the software availability and distribution.

## Requirements
* PHP >= 5.6

## Security
Please disclose any vulnerabilities found responsibly – report security issues to the maintainers privately.

## Installation
Using Composer:
```sh
composer require laswitchtech/php-router
```

## How do I use it?

### Skeleton
Let's start with the skeleton of your project directory.

```sh
├── .htaccess
├── index.php
├── Template
│   └── index.php
└── View
    ├── index.php
    └── 404.php
```

* .htaccess: This file will enable mod_rewrite in your project. If the file does not exist, phpRouter will create it.
* index.php: This file initiates the Router.
* Template/: This directory will contain your templates for your views.
* Template/index.php: This is a template file. These are used to load common Front-End components. For example a Sidebar.
* View/: This directory will contain your 404 view. But you can also use it to store additional views.
* View/404.php: This is the default 404 view file provided by the Router.
* View/index.php: This is the default index view file provided by the Router.

### Routes
Routes are what indicates the router which file to provide. For example for a route ```/``` the router will load with the file ```View/index.php```.

#### Defining Routes
You can define routes before loading/initiating the phpRouter. Note that specifying a template is optional.
```php
define("ROUTER_ROUTES", ['/' => ["view" => __DIR__ . '/View/index.php',"template" => __DIR__ . '/Template/index.php']]);
define("ROUTER_ROUTES", ['404' => ["view" => __DIR__ . '/View/404.php']]);
```

#### Adding Routes
This is done using the ```add``` method.
```php
$phpRouter->add(ROUTE, PATH_TO_VIEW_FILE, PATH_TO_TEMPLATE_FILE = NULL);
```

##### Example
```php

//Import API class into the global namespace
//These must be at the top of your script, not inside a function
use LaswitchTech\phpRouter\phpRouter;

//Load Composer's autoloader
require 'vendor/autoload.php';

//Initiate phpRouter
$phpRouter = new phpRouter();

//Add Routes
$phpRouter->add('/', __DIR__ . '/View/index.php');
```

### Forcing a View
This is done using the ```load``` method.
```php
$phpRouter->load(ROUTE | NULL = REQUEST_URI);
```

#### Example
```php

//Import API class into the global namespace
//These must be at the top of your script, not inside a function
use LaswitchTech\phpRouter\phpRouter;

//Load Composer's autoloader
require 'vendor/autoload.php';

//Initiate phpRouter
$phpRouter = new phpRouter();

//Add Routes
$phpRouter->load("500");
```

### Rendering a View
This is done using the ```render``` method.
```php
$phpRouter->render();
```

#### Example
```php

//Import API class into the global namespace
//These must be at the top of your script, not inside a function
use LaswitchTech\phpRouter\phpRouter;

//Load Composer's autoloader
require 'vendor/autoload.php';

//Initiate phpRouter
$phpRouter = new phpRouter();

//Render
$phpRouter->render();
```
