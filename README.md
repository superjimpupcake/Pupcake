Pupcake --- a micro framework for PHP 5.3+
=======================================

##About Pupcake Framework
+ Pupcake is a minimal but extensible microframework for PHP 5.3+
+ Pupcake can be run in traditional web server such as Apache and can also run as a standalone async server using the AsyncServer plugin together with php-uv and php-httpparser
  (The standalone async server is at its very early stage and is under active development)
+ For more detail usages on using pupcake in general and on traditional web servers, please see https://github.com/superjimpupcake/Pupcake/wiki/_pages
+ To see what pupcake can do as a standalone async server, see this readme page, a lot of the async server features are experimental now but it will demonstrate what Pupcake can do.
+ Also check out http://pamground.com/pupcakeframework, it contains examples explaining how to use Pupcake framework.

##Installation:

###If you plan to use it on Apache
#### install package "Pupcake/Pupcake" using composer (http://getcomposer.org/)
####.htaccess File for Apache
    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^(.*)$ index.php/$1 [L]

###If you plan to use it as a standalone async server
#### install package "Pupcake/Pupcake" using composer (http://getcomposer.org/)
#### install pcntl extension for php (http://www.php.net/manual/en/book.pcntl.php)
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

### (Experimental and Demo only) A simple HTTPS server
The HTTP servers requires two files, privatekey.pem and certificate.pem
In our testing server in local, we can create these 2 files with the following commands:

####
  openssl genrsa -out privatekey.pem 1024 
  openssl req -new -key privatekey.pem -out certrequest.csr 
  openssl x509 -req -in certrequest.csr -signkey privatekey.pem -out certificate.pem

Assume we created those files in the server folder, we then can write a simple server as follows:
```php
<?php
//Assuming this is server/server.php and the composer vendor directory is ../vendor
require_once __DIR__.'/../vendor/autoload.php';
$app = new Pupcake\Pupcake();
$app->usePlugin("Pupcake\Plugin\AsyncServer");

$app->setSecure(array(
  'key' => __DIR__.'/privatekey.pem',
  'cert' => __DIR__.'/certificate.pem'
));

$app->listen("127.0.0.1", 9000);
$app->on("system.server.response.body", function($event) use ($app, $pm){
  return "hello";
});
$app->run();
```

### (Experimental and Demo only) The server side timer like Node.js
In the example below, we set up a timer that execute a callback function every 1 second and increment the counter, then we store the 
current counter value into the application's storage. Then on each request, we return the current counter value as the response.
```php
<?php
//Assuming this is server/server.php and the composer vendor directory is ../vendor
require_once __DIR__.'/../vendor/autoload.php';
$app = new Pupcake\Pupcake();
$app->usePlugin("Pupcake\Plugin\AsyncServer");

$app->listen("127.0.0.1", 8000);

$count = 0;
$app->getTimer()->setInterval(function($timer) use ($app, &$count) {
  $count ++;
  $app->storageSet("time_elapsed", $count);
}, 1000);

$app->on("system.server.response.body", function($event) use ($app){
  return $app->storageGet("time_elapsed");
});

$app->run();
```

### (Experimental and Demo only) Process forking in an async fashion
In the example below, we will create 2 processes, test1 and test2, test1 will sleep for 10 seconds and return a string "test 1", test2
will return a string "test 2" immediately, what we want is, run test1 and test2 as 2 separate process and return their outputs together 
on each request, and we do not want to block all the incoming requests. The process test1 and test2 are called process closures, since it 
take a closure function as the main code body and run as a separate process.
```php
<?php
//Assuming this is server/server.php and the composer vendor directory is ../vendor
require_once __DIR__.'/../vendor/autoload.php';

$app = new Pupcake\Pupcake();
$app->usePlugin("Pupcake\Plugin\AsyncServer");

$pm = $app->getProcessManager();
$pm->setMaxNumberOfProcess(10);

$pm->addProcess("test1", function(){
  sleep(10);
  return "test 1";
});

$pm->addProcess("test2", function(){
  return "hello test2";
});

$pm->addProcess(time(), function() use ($app, $pm) {
  $app->listen("127.0.0.1", 9000);
  $app->on("system.server.response.body", function($event) use ($app, $pm){
    $test1_output = $pm->getProcessOutput("test1");
    $test2_output = $pm->getProcessOutput("test2");
    return $test1_output.",".$test2_output;
  });
  $app->run();
});

$pm->run();
```
Now run php server/server.php and go to 127.0.0.1:9000, we should see ",hello test2", since process test1 does not return yet, it is still sleeping. 
After 10 seconds, go to 127.0.0.1:9000, we should see "test 1,hello test2" since now process test1 return "test1". 

### (Experimental and Demo only) Parallel running processes and different servers
In the example below, we have 2 processes running in parallel, and we also have 2 servers listening to different ports. 
We have 2 different servers listening both port 8000 and 9000. 
```php
<?php
//Assuming this is server/server.php and the composer vendor directory is ../vendor
require_once __DIR__.'/../vendor/autoload.php';
$app = new Pupcake\Pupcake();
$app->usePlugin("Pupcake\Plugin\AsyncServer");

$pm = $app->getProcessManager();
$pm->setMaxNumberOfProcessToRun(10);

$pm->addProcess("test1", function(){
  sleep(10);
  return "test 1";
});

$pm->addProcess("test2", function(){
  return "hello test2";
});

$pm->addProcess("server1", function() use ($app, $pm) {
  $app->listen("127.0.0.1", 9000);
  $app->on("system.server.response.body", function($event) use ($app, $pm){
    $test1_output = $pm->getProcessOutput("test1");
    $test2_output = $pm->getProcessOutput("test2");
    return $test1_output.",".$test2_output;
  });
  $app->run();
});

$pm->addProcess("server2", function() use ($app, $pm) {
  $app->listen("127.0.0.1", 8000);
  $app->on("system.server.response.body", function($event) use ($app, $pm){
    return "I am also listening port 8000, the output from test1 is ".$pm->getProcessOutput('test1');
  });
  $app->run();
});

$pm->run();
```
