Pupcake --- a micro framework for PHP 5.3+
=======================================

##About Pupcake Framework
Pupcake is a minimal but extensible microframework for PHP 5.3+. It has a powerful plugin and event handling system, which makes it "simple at the beginning, powerful at the end".

##Installation:

####install package "Pupcake/Pupcake" using composer (http://getcomposer.org/)

###.htaccess File for Apache
    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^(.*)$ index.php/$1 [L]

###Simple requests
```php
<?php
//Assuming this is public/index.php and the composer vendor directory is ../vendor

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

/**
 * Multiple request methods for one route
 */
$app->map("/api/hello/:action", function($action){
  return "hello ".$name." in get and post";
})->via('GET','POST');


$app->run();
```

####Use Pupcake like Express Node.js framework
For all developers who are also a Express Node.js framework user, you will probably want to use something like the following:
```php
$app->get("date/:year/:month/:day", function($req, $res){
    $output = $req->params('year').'-'.$req->params('month').'-'.$req->params('day');
    $res->send($output);
});
```
Pupcake provide the plugin named "Express" to help with that
```php
<?php
//Assuming this is public/index.php and the composer vendor directory is ../vendor

require_once __DIR__.'/../vendor/autoload.php';

$app = new Pupcake\Pupcake();
$app->usePlugin("Pupcake.Plugin.Express");

$app->get("date/:year/:month/:day", function($req, $res){
    $output = $req->params('year').'-'.$req->params('month').'-'.$req->params('day');
    $res->send($output);
});

$app->run();
```

###Using constraint in route and using $next function to find the next route like Express framework
```php
<?php
//Assuming this is public/index.php and the composer vendor directory is ../vendor

require_once __DIR__.'/../vendor/autoload.php';

$app = new Pupcake\Pupcake();

$app->usePlugin("Pupcake\Plugin\Express"); //note that we can use both \ and . in the plugin name
$app->usePlugin("Pupcake\Plugin\RouteConstraint"); 

$app->any("api/12", function($req, $res, $next){
    $next();
});

$app->any("api/:number", function($req, $res, $next){
    $next();
})->constraint(array(
    'number' => function($value){
        $result = true;
        if($value < 15){
            $result = false;
        }
        return $result;
    }
));

$app->get("api/12", function($req, $res, $next){
    $next();
});

$app->get("api/:number", function($req, $res, $next){
    $res->send("this is finally number ".$req->params('number'));
});


$app->run();
```

###Event Helper and Event Handler
In Pupcake framework, each event can have only one event handler, it is swappable. Each event can register one or many event helpers to join the process of the event handling.
```php
<?php
//Assuming this is public/index.php and the composer vendor directory is ../vendor

require_once __DIR__.'/../vendor/autoload.php';

$app = new Pupcake\Pupcake();

$app->usePlugin("Pupcake\Plugin\Express"); //note that we can use both \ and . in the plugin name

/**
 * we override the system.request.found event's handler
 */
$app->on("system.request.found", function($event){
    /**
     * We register 3 event helper callbacks for the sytem.request.found event
     */
    $results = $event->register(
        function(){
            return "output 1";
        },
        function(){
            return "output 2";
        },
        function(){
            return "output 3"; 
        }
    )->start();

    $output = "";
    if(count($results) > 0){
        foreach($results as $result){
            $output .= $result;
        } 
    }

    return $output;
});

$app->any("*path", function($req, $res){
});

$app->run();
```

### Custom Event Triggering and Handling
```php
<?php
//Assuming this is public/index.php and the composer vendor directory is ../vendor

require_once __DIR__.'/../vendor/autoload.php';

$app = new Pupcake\Pupcake();

$app->usePlugin("Pupcake\Plugin\Express"); //load Plugin

/**
 * We define a custom event handler for the node.view event
 */
$app->on("node.view", function($event){
    return "viewing node id ".$event->props('id');
});

$app->any("node/:id", function($req, $res) use ($app) {
    /**
     * We trigger the node.view event when we have node/1, node/2... in the request path
     * The second parameter in the trigger method is empty string since we don't have 
     * a default event handler callback for the node.view event
     */
    $output = $app->trigger("node.view", "", array('id' => $req->params('id')));
    $res->send($output);
});

$app->run();
```
