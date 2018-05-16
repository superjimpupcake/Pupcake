Pupcake --- a micro framework for PHP 5.3+
=======================================

##About Pupcake Framework
+ Pupcake is a minimal but extensible microframework for PHP 5.3+
+ Pupcake can be run in traditional web server such as Apache.
+ For more detail usages on using pupcake in general and on traditional web servers, please see https://github.com/superjimpupcake/Pupcake/wiki/_pages

## Installation:

### If you plan to use it on Apache
#### install package "Pupcake/Pupcake" using composer (http://getcomposer.org/)
#### .htaccess File for Apache
    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^(.*)$ index.php/$1 [L]

### Simple requests when running on Apache
#### For more details on running Pupcake in general and in traditional web server, please see https://github.com/superjimpupcake/Pupcake/wiki/_pages
```php
<?php
//Assuming this is public/index.php and the composer vendor directory is ../vendor

require_once __DIR__.'/../vendor/autoload.php';

$app = new Pupcake\Pupcake();

$app->get("date/:year/:month/:day", function($req, $res){
    $output = $req->params('year').'-'.$req->params('month').'-'.$req->params('day');
    $res->send($output);
});

$app->get("/hello/:name", function($req, $res){
  $res->send("hello ".$req->params('name')." in get");
});

$app->post("/hello/:name", function($req, $res){
  $res->send("hello ".$req->params('name')." in post");
});

$app->put("/hello/:name", function($req, $res){
  $res->send("hello ".$req->params('name')." in put");
});

$app->delete("/hello/:name", function($req, $res){
  $res->send("hello ".$req->params('name')." in delete");
});

/**
 * Multiple request methods for one route
 */
$app->map("/api/hello/:action", function($req, $res){
  $res->send("hello ".$req->params('action')." in get and post");
})->via('GET','POST');


$app->run();
```
