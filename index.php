<?php
require "pupcake.php";

$app = new \Pupcake\Pupcake();

$app->get("/hello/:name", function($name){
  return $name;
});

$app->get("test", function(){
   return \Pupcake\Router::instance()->redirect("test2");
});

$app->any("/:year/:month/:day", function($year, $month, $day){
  return $year."-".$month."-".$day;
});

$app->get("/test2", function(){
  return "testing 2";
});

$app->notFound(function(){
  return "not found any routes!";
});

$app->run();
