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
* Apache2

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

### Requirements
Requirements are what indicates the router if it can run the application or not. phpRouter can check both Apache2 modules and PHP modules. If some requirements are not met, phpRouter will throw a HTTP/1.1 500 Internal Error header. Along with some information about the unmet requirement.

#### Adding Requirements
You can define requirements before loading/initiating the phpRouter.
```php
define("ROUTER_REQUIREMENTS", ["APACHE" => ["module1","module2"],"PHP" => ["module1","module2"]]);
```

### Routes
Routes are what indicates the router which file to provide. For example for a route ```/``` the router will load with the file ```View/index.php```.

#### Available Parameters
Routes have multiple parameters. Here is a list:
* ```view = NULL```: This indicates the view file you want to render.
* ```template = NULL```: This indicates the template file you want to render. The template file always overwrites the view file.
* ```public = true```: This indicates wether or not a route is public. This is done by looking if ```$_SESSION['ID']``` exist.
* ```error = NULL```: This indicates the route to use if the public parameter is not met.

#### Defining Routes
You can define routes before loading/initiating the phpRouter. Note that specifying a template is optional.
```php
define('ROUTER_ROUTES',[
  "404" => ["view" => "View/404.php"],
  "/" => ["view" => "View/index.php", "template" => "Template/index.php", "public" => false, "error" => "/signin"],
  "/signin" => ["view" => "View/signin.php", "label" => "Sign In"],
  "/info" => ["view" => "View/info.php", "label" => "PHP Info"],
]);
```

### Parsing URL for Variables
This is done using the ```parseURI``` method.
```php
$phpRouter->parseURI();
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

//Parsing URL Variables
$phpRouter->parseURI();
```

### Forcing a specific View
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

//Loading route "500"
$phpRouter->load("500");
```

### Rendering the View
This is done using the ```render``` method.
```php
$phpRouter->render();
```

Note that you can also access the router within the view & template file using ```$this``` as your reference. For exemple : ```$this->parseURI();```.

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
