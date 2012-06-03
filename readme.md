Pupcake --- a micro framework for PHP 5.3+
=======================================

##Usage:

###Simple get,post,put,delete requests
```php
<?php
require "pupcake.php";

$app = new \Pupcake\Pupcake();

$app->get("/hello/:name", function($name){
  return "hello ".$name." in get";
});

$app->post("/hello/:name", function($name){
  return "hello ".$name." in post";
});

$app->put("/hello/:name", function($name){
  return "hello ".$name." in put";
});

$app->delete("/hello/:name", function($name){
  return "hello ".$name." in delete";
});

$app->run();
```

###Multiple request methods for one route
```php
<?php
require "pupcake.php";

$app = new \Pupcake\Pupcake();

$app->map("/hello/:name", function($name){
  return "hello ".$name." in get and post";
})->via('GET','POST');

$app->run();
```


###Request redirection
```php
<?php
require "pupcake.php";

$app = new \Pupcake\Pupcake();

$app->post("/hello/:name", function($name) use ($app) {
  $app->redirect("/test");
});

$app->run();
```

###Request forwarding (internal request)
```php
<?php
require "pupcake.php";

$app = new \Pupcake\Pupcake();

$app->get("/hello/:name", function($name){
  return $name;
});

$app->post("/hello/:name", function($name){
  return "posting $name to hello";
});

$app->get("test", function() use ($app){
 return $app->redirect("test2");
});

$app->any("date/:year/:month/:day", function($year, $month, $day){
  return $year."-".$month."-".$day;
});

$app->get("/test2", function(){
  return "testing 2";
});

$app->get("test_internal", function() use ($app) {
  $content = "";
  $content .= $app->forward("POST", "hello/world")."<br/>";
  $content .= $app->forward("GET", "hello/world2")."<br/>";
  $content .= $app->forward("GET", "hello/world3")."<br/>";
  $content .= $app->forward("GET", "test")."<br/>";
  $content .= $app->forward("POST", "date/2012/05/30")."<br/>";
  return $content;
});

$app->run();
```

###Custom request-not-found handler
```php
<?php
require "pupcake.php";

$app = new \Pupcake\Pupcake();

$app->notFound(function(){
    return "not found any routes!";
});

$app->run();
```

###Catch any requests
```php
<?php
require "pupcake.php";

$app = new \Pupcake\Pupcake();

$app->any(":path", function($path){
    return "the current path is ".$path;
});

$app->run();
```

###Request type detection in internal and external request
```php
<?php
require "pupcake.php";

$app = new \Pupcake\Pupcake();

$app->post('api/me/update', function() use ($app) {
    return $app->getRequestType();
});

$app->get('test', function() use ($app) {
    return $app->getRequestType().":".$app->forward('POST','api/me/update');
});

$app->run();
```

###Advance Event handling --- detect request not found
```php
<?php
require "pupcake.php";

$app = new \Pupcake\Pupcake();

/**
 * This is the same as 
 * $app->notFound(function(){
 *   return "request not found handler";
 * });
 */

$app->on('system.request.notfound', function(){
    return "request not found handler";
});

$app->run();
```

###Advanced Event Handling --- detect system error
```php
<?php
require "pupcake.php";

$app = new \Pupcake\Pupcake();

/**
 * By defining custom callback for system.error.detected event, 
 * we can build a custom error handling system
 */
$app->on('system.error.detected', function($error){
    $message = $error->getMessage();
    $line = $error->getLine();
    print $message." at ".$line."\n";
});

print $output; //undefined variable

$app->run();
```

###Advanced Event Handling --- custom response output
####We can "intercept" the output generation process when request is found and a route is matched
```php
<?php
require "pupcake.php";

$app = new \Pupcake\Pupcake();

$app->get("/hello/:name", function($name){
  return $name;
});

$app->on('system.request.found', function($route) use ($app) {
    return "prepend outputs ".$app->executeRoute($route);
});

$app->run();
```
###Advanced Event Handling --- system shutdown detection
####We can hook into the system.shutdown event
```php
<?php
require "pupcake.php";

$app = new \Pupcake\Pupcake();

$app->on('system.shutdown', function(){
    print "<br/>system is shutdown now<br/>";
});

$app->run();
```
###Flexible bridging system to 3rd party libraries
####Render jade template by bridging jade_php 
####For detail usage of jade php, see: https://github.com/everzet/jade.php
```php
<?php
require "pupcake.php";

$app = new \Pupcake\Pupcake();

$app->get("/jadedemo", function($name) use ($app) {
    $output = $app->bridge('jade_php')->render("!!! 5\np testing");
    return $output;
});

$app->run();
```
####Validation on route parameters by bridging respect_validation
#####For detail usage of Respect/Validation, see: https://github.com/Respect/Validation 
```php
<?php
require "pupcake.php";

use Respect\Validation\Validator as v;

$app = new \Pupcake\Pupcake();

$app->get("/hello/:number", function($number) use ($app) {
    $app->bridge('respect_validation');
    $is_numeric = v::numeric()->validate($number);
    if($is_numeric){
        return "this is a numeric value";
    }
    else{
        return "this is a non-numeric value";
    }
});

$app->run();
```
