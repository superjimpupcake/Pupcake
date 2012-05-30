<?php
require "pupcake.php";

$app = new \Pupcake\Pupcake();

$app->get("/hello/:name", function($name){
  return $name;
});

$app->get("/test", function(){
   return "test";
});

$app->any("/:year/:month/:day", function($year, $month, $day){
  return $year."-".$month."-".$day;
});

$app->notFound(function(){
  return "not found any routes!";
});

$app->run();
