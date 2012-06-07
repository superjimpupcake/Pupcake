Pupcake --- a micro framework for PHP 5.3+
=======================================

##About Pupcake Framework
Pupcake is a minimal but extensible microframework for PHP 5.3+. Unlike many other frameworks, it does not have built-in support for regular expressions in route matching, it only matches name-based route tokens.
The regular expression part ( or route validation ) can be handled with 3rd party packages through the framework's event-based system.

##Installation:

####install package "Pupcake/Pupcake" using composer (http://getcomposer.org/) (recommened)
####or manually include it by doing: 
    require "[PATH/TO/Pupcake]/Pupcake/Pupcake.php"

###.htaccess File for Apache
    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^(.*)$ index.php/$1 [L]

##Usage:

###Simple get,post,put,delete requests
```php
<?php
//Assiming this is public/index.php and the composer vendor directory is ../vendor

require_once __DIR__.'/../vendor/autoload.php';

$app = new Pupcake\Pupcake();

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
//Assiming this is public/index.php and the composer vendor directory is ../vendor

require_once __DIR__.'/../vendor/autoload.php';

$app = new Pupcake\Pupcake();

$app->map("/hello/:name", function($name){
  return "hello ".$name." in get and post";
})->via('GET','POST');

$app->run();
```


###Request redirection
```php
<?php
//Assiming this is public/index.php and the composer vendor directory is ../vendor

require_once __DIR__.'/../vendor/autoload.php';

$app = new Pupcake\Pupcake();

$app->post("/hello/:name", function($name) use ($app) {
  $app->redirect("/test");
});

$app->run();
```

###Request forwarding (internal request)
```php
<?php
//Assiming this is public/index.php and the composer vendor directory is ../vendor

require_once __DIR__.'/../vendor/autoload.php';

$app = new Pupcake\Pupcake();

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
//Assiming this is public/index.php and the composer vendor directory is ../vendor

require_once __DIR__.'/../vendor/autoload.php';

$app = new Pupcake\Pupcake();

$app->notFound(function(){
    return "not found any routes!";
});

$app->run();
```

###Catch any requests
```php
<?php
//Assiming this is public/index.php and the composer vendor directory is ../vendor

require_once __DIR__.'/../vendor/autoload.php';

$app = new Pupcake\Pupcake();

$app->any(":path", function($path){
    return "the current path is ".$path;
});

$app->run();
```

###Request type detection in internal and external request
```php
<?php
//Assiming this is public/index.php and the composer vendor directory is ../vendor

require_once __DIR__.'/../vendor/autoload.php';

$app = new Pupcake\Pupcake();

$app->post('api/me/update', function() use ($app) {
    return $app->getRequestType();
});

$app->get('test', function() use ($app) {
    return $app->getRequestType().":".$app->forward('POST','api/me/update');
});

$app->run();
```

###Custom Event handling --- detect request not found
```php
<?php
//Assiming this is public/index.php and the composer vendor directory is ../vendor

require_once __DIR__.'/../vendor/autoload.php';

$app = new Pupcake\Pupcake();

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

###Custom Event Handling --- detect system error
```php
<?php
//Assiming this is public/index.php and the composer vendor directory is ../vendor

require_once __DIR__.'/../vendor/autoload.php';

$app = new Pupcake\Pupcake();

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

###Custom Event Handling --- custom response output
####We can "intercept" the output generation process when request is found and a route is matched
```php
<?php
//Assiming this is public/index.php and the composer vendor directory is ../vendor

require_once __DIR__.'/../vendor/autoload.php';

$app = new Pupcake\Pupcake();

$app->get("/hello/:name", function($name){
  return $name;
});

$app->on('system.request.found', function($route) use ($app) {
    return "prepend outputs ".$app->executeRoute($route);
});

$app->run();
```
###Custom Event Handling --- system shutdown detection
####We can hook into the system.shutdown event
```php
<?php
//Assiming this is public/index.php and the composer vendor directory is ../vendor

require_once __DIR__.'/../vendor/autoload.php';

$app = new Pupcake\Pupcake();

$app->on('system.shutdown', function(){
    print "<br/>system is shutdown now<br/>";
});

$app->run();
```
###Custom Event Handling --- set up services to do validation on route parameters
####We can create arbitary service events to hook up to Respect/Validation package (https://github.com/Respect/Validation)
```php
<?php
//Assiming this is public/index.php and the composer vendor directory is ../vendor

require_once __DIR__.'/../vendor/autoload.php';

/**
 * First, we need to make sure Respect/Validation package is installed properly via composer
 */
$app = new Pupcake\Pupcake();

$app->on('service.validation', function(){
    $validator = array();
    foreach(array('numeric','email','ip') as $type){
        $validator[$type] = call_user_func("Respect\Validation\Validator::$type");
    }
    return $validator;
});

$app->get("hello/:string", function($string) use ($app){
    $validator = $app->trigger('service.validation');
    if($validator['numeric']->validate($string)){
        return "number detected";
    }
    else if($validator['email']->validate($string)){
        return "email detected";
    }
    else if($validator['ip']->validate($string)){
        return "ip detected";
    }

});
$app->run();
```
###Custom Event Handling --- set up services to render twig templates
####We can create arbitary service events to hook up to twig/twig package (http://github.com/fabpot/Twig.git)
```php
<?php
//Assiming this is public/index.php and the composer vendor directory is ../vendor

require_once __DIR__.'/../vendor/autoload.php';

/**
 * First, we need to make sure twig/twig package is installed properly via composer
 * Also, the views folder and ../views/index.html file should be created and has proper write permissions for the server
 */
$app = new Pupcake\Pupcake();

$app->on('service.twig.template', function(){
    $loader = new Twig_Loader_Filesystem("../views");
    $twig = new Twig_Environment($loader);
    return $twig;
});

$app->get("hello/:string", function($string) use ($app){
    $template = $app->trigger('service.twig.template');
    return $template->loadTemplate('index.html')->render(array('string' => $string));
});
$app->run();
```
###Custom Event Handling --- set up services to render php templates using kaloa/view
####We can create arbitary service events to hook up to kaloa/view package (https://github.com/mermshaus/kaloa-view)
```php
<?php

//Assiming this is public/index.php and the composer vendor directory is ../vendor

require_once __DIR__.'/../vendor/autoload.php';

/**
 * First, we need to make sure kaloa/view package is installed properly via composer
 * Also, the ../views/index.phtml file should be created and has proper write permissions for the server
 */
$app = new Pupcake\Pupcake();

$app->on('service.kaloa.view', function(){
    $view = new Kaloa\View\View();
    return $view;
});

$app->get("hello/:string", function($string) use ($app){
    $view = $app->trigger('service.kaloa.view');
    $view->string = $string;
    return $view->render("../views/index.phtml");
});
$app->run();
```
###Advance Usage: dynamic method creation
All Pupcake system objects (Object, EventManager, Route, Router, Pupcake) has a powerful method named "method", it allows you to dynamically define a  method that
is not defined yet
```php
<?php
//Assiming this is public/index.php and the composer vendor directory is ../vendor

require_once __DIR__.'/../vendor/autoload.php';

$app = new Pupcake\Pupcake();
$app->method("hello", function($string){
    return "hello $string";
});
print $app->hello("world");
```
###Advance Usage: use dynamic method creation on Pupcake\Ojbect to create a twig rendering service
In The previous example on twig template rendering, we need to use 
```php 
$template->loadTemplate('index.html')->render(array('string' => $string));
```
to render the template, what if we want something like
```php
$view->render([template], array('[token1] => '[value1],'[token2] => '[value2]')) 
```
similar to what codeigniter does?
We can achieve this with dynamic method creation without even defining our own class
```php
<?php
//Assiming this is public/index.php and the composer vendor directory is ../vendor

require_once __DIR__.'/../vendor/autoload.php';

/**
 * Make sure ../views/index.html exists and twig/twig package is installed properly through composer
 */
$app = new Pupcake\Pupcake();

$app->on('service.view.twig', function(){
    $view = new Pupcake\Object();
    //we add a dynamic method named render
    $view->method("render", function($template_file,$data = array() ){
        $loader = new Twig_Loader_Filesystem(dirname($template_file));
        $twig = new Twig_Environment($loader);
        return $twig->loadTemplate(basename($template_file))->render($data);
    });
    return $view;
});

$app->get("twigdemo", function() use ($app){
    $view = $app->trigger('service.view.twig');
    return $view->render("../views/index.html", array('string' => 'jim')); //now we can render the template similar to the codeigniter way!
});

$app->run();
```
###Advance Usage: adding constraints in route
####We can create constraints in route by hooking into system.routing.route.create event and system.routing.route.matched event
####Also Check out https://github.com/superjimpupcake/PupcakeRespectRoute
```php
<?php
//Assiming this is public/index.php and the composer vendor directory is ../vendor

require_once __DIR__.'/../vendor/autoload.php';

/**
 * First, we need to make sure Respect/Validation package is installed 
 * properly via composer
 */
$app = new Pupcake\Pupcake();

/**
 * When a route object is being created, we add the constraint method 
 * to it and store the constraint into this route object's storage
 */
$app->on("system.routing.route.create", function(){
    $route = new Pupcake\Route();
    $route->method('constraint', function($constraint) use($route){
        $route->storageSet('constraint', $constraint);
    });
    return $route;
});

/**
 * When a route object is initially matched, we add further checking logic 
 * to make sure the constraint is applying toward the route matching process
 */
$app->on("system.routing.route.matched", function($route){
    $matched = true;
    $params = $route->getParams();
    $constraint = $route->storageGet('constraint');
    if(count($constraint) > 0){
        foreach($constraint as $token => $validation_callback){
            if(is_callable($validation_callback)){
                if(!$validation_callback($params[$token])){
                    $matched = false;
                    break;
                }
            }
        }
    } 
    return $matched;
});

$app->get("api/validate/:token", function($token){
    return $token;
})->constraint(array(
    ':token' => function($value){
        return Respect\Validation\Validator::date('Y-m-d')
        ->between('1980-02-02', '2015-12-25')
        ->validate($value);
    }
));

$app->run();
```
