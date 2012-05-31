<?php
require "pupcake.php";

$app = new \Pupcake\Pupcake();

$app->get("/", function(){
  return "hello";
});

$app->run();
