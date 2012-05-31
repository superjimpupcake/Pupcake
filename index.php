<?php
require "pupcake.php";

$app = new \Pupcake\Pupcake();

$app->get("/", function(){
  return "default response";
});

$app->get("/hello/:name", function($name){
  return $name;
});

$app->post("/hello/:name", function($name){
  return "posting $name to hello";
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

$app->any("*", function(){
  return "all routes finally go here";
});

$app->notFound(function(){
  return "not found any routes!";
});

$app->get("test_internal", function() use ($app) {
  $content = "";
  $content .= $app->sendInternalRequest("POST", "hello/world")."<br/>";
  $content .= $app->sendInternalRequest("GET", "hello/world2")."<br/>";
  $content .= $app->sendInternalRequest("GET", "hello/world3")."<br/>";
  $content .= $app->sendInternalRequest("GET", "test2")."<br/>";
  $content .= $app->sendInternalRequest("POST", "2012/05/30")."<br/>";
  return $content;
});

$app->run();
