<?php

require "pupcake.php";

$app = new \Pupcake\Pupcake();

$app->on('system.components.autoload.mapping', function(){
    return array(
    );
});

$app->get("/hello/:name", function($name) use ($app) {
    $output = $app->getComponent('jade_php')->render("html");
    return $output;
});

$app->run();
