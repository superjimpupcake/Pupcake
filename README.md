Pupcake --- a micro framework for PHP 5.3+
=======================================

##About Pupcake Framework
  Pupcake is a minimal but extensible microframework for PHP 5.3+
  Pupcake can be run in traditional web server such as Apache and can also run as a standalone async server using the AsyncServer plugin together with php-uv and php-httpparser
  For more detail usages, please see https://github.com/superjimpupcake/Pupcake/wiki/_pages

##Installation:

###If you plan to use it on Apache
#### install package "Pupcake/Pupcake" using composer (http://getcomposer.org/)
####.htaccess File for Apache
    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^(.*)$ index.php/$1 [L]

###If you plan to use it as a standalone async server
#### install package "Pupcake/Pupcake" using composer (http://getcomposer.org/)
#### install php-uv and php-httpparser
    git clone https://github.com/chobie/php-uv.git --recursive
    cd php-uv/libuv
    make && cp uv.a libuv.a
    cd ..
    phpize
    ./configure
    make && make install

    git clone https://github.com/chobie/php-httpparser.git --recursive
    cd php-httpparser
    phpize
    ./configure
    make && make install

    add following extensions to your php.ini
    extension=uv.so
    extension=httpparser.so


###Simple requests when running on Apache
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

### A simple standalone server to listen to port 9000 in local
Write a php script named server.php in the server folder
```php
<?php
//Assuming this is server/server.php and the composer vendor directory is ../vendor
require_once __DIR__.'/../vendor/autoload.php';

$app = new Pupcake\Pupcake();

$app->usePlugin("Pupcake\Plugin\AsyncServer");

$app->listen("127.0.0.1", 9000);

$app->get("/", function($req, $res){
  $res->sendJSON(array('ok' => true));
});

$app->get("/hello", function($req, $res){
  $res->sendJSON(array('word' => 'hello'));
});

$app->run();
```
Now run php server/server.php and go to http://127.0.0.1:9000 to see the result
