Pupcake --- a micro framework for PHP 5.3+
=======================================

##Pupcake is a micro framework for PHP 5.3+

##Usage:

###Simple get request
```php
    <?php
    require "pupcake.php";

    $app = new \Pupcake\Pupcake();

    $app->get("/hello/:name", function($name){
      return "hello ".$name;
    });

    $app->run();
```

###Simple post request
```php
    <?php
    require "pupcake.php";

    $app = new \Pupcake\Pupcake();

    $app->post("/hello/:name", function($name){
      return "hello ".$name;
    });

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
