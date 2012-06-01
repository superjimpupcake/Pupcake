Pubcake, a micro framework for PHP 5.3+
=============================

Pubcake is a micro framework for PHP 5.3+

Usage:

1. Simple get request
```php
<?php
require "pupcake.php";

$app = new \Pupcake\Pupcake();

$app->get("/hello/:name", function($name){
  return "hello ".$name;
});

$app->run();
```

2. Simple post request
```php
<?php
require "pupcake.php";

$app = new \Pupcake\Pupcake();

$app->post("/hello/:name", function($name){
  return "hello ".$name;
});

$app->run();
```

3. Request direction
```php
<?php
require "pupcake.php";

$app = new \Pupcake\Pupcake();

$app->post("/hello/:name", function($name) use ($app) {
$app->redirect("/test");
});

$app->run();
```
