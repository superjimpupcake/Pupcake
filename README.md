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

### A simple standalone server returning "Hello Word"
We can return any custom output by skipping the whole routing process by hooking up to system.server.response.body event
```php
<?php
require_once __DIR__.'/../vendor/autoload.php';

$app = new Pupcake\Pupcake();

$app->usePlugin("Pupcake\Plugin\AsyncServer");

$app->listen("127.0.0.1", 9000);

$app->on("system.server.response.body", function($event){
  return "hello world";
});

$app->run();
```

This might need more investigation, but the script above seems to be able handle more requests per seconds than Node.js

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
ab -n 1000 -c 20 http://127.0.0.1:9000/ (our php server)

    Server Software:        
    Server Hostname:        127.0.0.1
    Server Port:            9000

    Document Path:          /
    Document Length:        11 bytes

    Concurrency Level:      20
    Time taken for tests:   0.194 seconds
    Complete requests:      1000
    Failed requests:        0
    Write errors:           0
    Total transferred:      30000 bytes
    HTML transferred:       11000 bytes
    Requests per second:    5148.35 [#/sec] (mean)
    Time per request:       3.885 [ms] (mean)
    Time per request:       0.194 [ms] (mean, across all concurrent requests)
    Transfer rate:          150.83 [Kbytes/sec] received

ab -n 1000 -c 20 http://127.0.0.1:1337/ (the node.js hello world script)

    Server Software:        
    Server Hostname:        127.0.0.1
    Server Port:            1337

    Document Path:          /
    Document Length:        12 bytes

    Concurrency Level:      20
    Time taken for tests:   0.257 seconds
    Complete requests:      1000
    Failed requests:        0
    Write errors:           0
    Total transferred:      113000 bytes
    HTML transferred:       12000 bytes
    Requests per second:    3884.73 [#/sec] (mean)
    Time per request:       5.148 [ms] (mean)
    Time per request:       0.257 [ms] (mean, across all concurrent requests)
    Transfer rate:          428.69 [Kbytes/sec] received
