<?php
namespace Pupcake\Service\Express;

use Pupcake;

class Response extends Pupcake\Object
{
    private $app;
    private $route;

    public function __construct($app, $route)
    {
        $this->app = $app;
        $this->route = $route;
    }

    public function send($output)
    {
        $this->route->storageSet('output', $output); 
    }

    public function redirect($uri)
    {
        $this->app->redirect($uri);
    }

    public function forward($request_type, $uri)
    {
        $this->app->forward($request_type, $uri);
        $route = $this->app->getRouter()->getMatchedRoute();
        return $route->storageGet('output');
    }
}

