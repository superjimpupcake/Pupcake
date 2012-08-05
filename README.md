Pupcake --- a micro framework for PHP 5.3+
=======================================

##About Pupcake Framework
+ Pupcake is a minimal but extensible microframework for PHP 5.3+
+ Pupcake can be run in traditional web server such as Apache and can also run as a standalone async server using the AsyncServer plugin together with php-uv and php-httpparser
  (The standalone async server is at its very early stage and is under active development)
+ For more detail usages, please see https://github.com/superjimpupcake/Pupcake/wiki/_pages

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
    make && cp uv.a libuv.a (my experience on both centos and ubuntu server is, we need to add -fPIC flag to cc)
    cd ..
    phpize
    ./configure
    make && make install (my experience on both centos and ubuntu server is, we need to add -fPIC flag to cc)

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

### A simple standalone server returning "Hello Word", benchmarked with node.js
We can return any custom output by skipping the whole routing process by hooking up to system.server.response.body event
```php
<?php
require_once __DIR__.'/../vendor/autoload.php';

$app = new Pupcake\Pupcake();

$app->usePlugin("Pupcake\Plugin\AsyncServer");

$app->listen("127.0.0.1", 9000);

$app->on("system.server.response.body", function($event){
  return "hello world\n";
});

$app->run();
```

This might need more investigation, but the script above seems to be able to handle more requests per seconds than Node.js

Benchmarking compared with the following node.js script
```javascript
var http = require('http');
http.createServer(function (req, res) {
    res.writeHead(200, {'Content-Type': 'text/plain'});
    res.end('Hello World\n');
    }).listen(1337, '127.0.0.1');
console.log('Server running at http://127.0.0.1:1337/');
```
Below are the data return by apache ab:
ab -n 100000 -c 200 http://127.0.0.1:9000/ (our php server)

    Concurrency Level:      200
    Time taken for tests:   22.338 seconds
    Complete requests:      100000
    Failed requests:        0
    Write errors:           0
    Total transferred:      3100000 bytes
    HTML transferred:       1200000 bytes
    Requests per second:    4476.65 [#/sec] (mean)
    Time per request:       44.676 [ms] (mean)
    Time per request:       0.223 [ms] (mean, across all concurrent requests)
    Transfer rate:          135.52 [Kbytes/sec] received

ab -n 100000 -c 200 http://127.0.0.1:1337/ (the node.js hello world script run with node version 0.8.5)

    Concurrency Level:      200
    Time taken for tests:   25.660 seconds
    Complete requests:      100000
    Failed requests:        0
    Write errors:           0
    Total transferred:      11300000 bytes
    HTML transferred:       1200000 bytes
    Requests per second:    3897.19 [#/sec] (mean)
    Time per request:       51.319 [ms] (mean)
    Time per request:       0.257 [ms] (mean, across all concurrent requests)
    Transfer rate:          430.06 [Kbytes/sec] received
