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
$app = new Pupcake\Pupcake();

$app->map("/hello/:name", function($name){
  return "hello ".$name." in get and post";
})->via('GET','POST');

$app->run();
```


###Request redirection
```php
<?php
$app = new Pupcake\Pupcake();

$app->post("/hello/:name", function($name) use ($app) {
  $app->redirect("/test");
});

$app->run();
```

###Request forwarding (internal request)
```php
<?php
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
$app = new Pupcake\Pupcake();

$app->notFound(function(){
    return "not found any routes!";
});

$app->run();
```

###Catch any requests
```php
<?php
$app = new Pupcake\Pupcake();

$app->any(":path", function($path){
    return "the current path is ".$path;
});

$app->run();
```

###Request type detection in internal and external request
```php
<?php
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
/**
 * First, we need to make sure twig/twig package is installed properly via composer
 * Also, the views folder and views/index.html file should be created and has proper write permissions for the server
 */
$app = new Pupcake\Pupcake();

$app->on('service.twig.template', function(){
    $loader = new Twig_Loader_Filesystem("views");
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
/**
 * First, we need to make sure kaloa/view package is installed properly via composer
 * Also, the views/index.phtml file should be created and has proper write permissions for the server
 */
$app = new Pupcake\Pupcake();

$app->on('service.kaloa.view', function(){
    $view = new Kaloa\View\View();
    return $view;
});

$app->get("hello/:string", function($string) use ($app){
    $view = $app->trigger('service.kaloa.view');
    $view->string = $string;
    return $view->render("views/index.phtml");
});
$app->run();
```
###Advance Usage: dynamic method creation
####We can dynamically create new methods in pupcake system through the method call
```php
<?php
$app = new Pupcake\Pupcake();
$app->method("hello", function($string){
    return "hello $string";
});
print $app->hello();
```
###Advance Usage: adding constraints in route
####We can create constraints in route by hooking into system.routing.route.create event and system.routing.route.matched event
```php
<?php
/**
 * First, we need to make sure Respect/Validation package is installed properly via composer
 */
$app = new Pupcake\Pupcake();

/**
 * Define RespectRoute class, it can be in a separate package
 */
class RespectRoute extends Pupcake\Route
{
    private $route_constraint;

    public function constraint($route_constraint = array())
    {
       $this->route_constraint = $route_constraint;
    }

    public function getConstraint()
    {
        return $this->route_constraint;
    }

    /**
     * This is called after a route is matched
     */
    public function matched(){
        $matched = true;
        $constraint = $this->getConstraint();
        $params = $this->getParams();
        if(count($constraint) > 0){
            $validator_params = array();
            foreach($constraint as $token => $validator_name){
                if($validator_name[0] == '@'){
                    $validator_name[0] = '';
                    $validator_name = trim($validator_name);
                }
                else{
                    $validator_params = array($validator_name); //this is a regex!
                    $validator_name = "regex";
                }
                $validator = call_user_func_array("Respect\Validation\Validator::$validator_name", $validator_params);
                if(!$validator->validate($params[$token])){
                    $matched = false;
                    break;
                }
            }
        } 
        return $matched;
    }
}

$app->on("system.routing.route.create", function(){
    return new RespectRoute();
});

/**
 * match regex
 */
$app->get("hello/:string", function($string){
    return $string;
})->constraint(array(
    ':string' => '/^[0-9]$/'
));

/**
 * match email 
 */
$app->get("api/email/:string", function($string){
    return $string;
})->constraint(array(
    ':string' => '@email'
));

/**
 * match regular expression
 */
$app->get("api/regex/:string", function($string){
    return $string;
})->constraint(array(
    ':string' => '/^[a-z]$/'
));

$app->run();
```
