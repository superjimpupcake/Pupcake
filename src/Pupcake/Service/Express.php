<?php
/**
 * Express Service
 */
namespace Pupcake\Service;

use Pupcake;

class ExpressRequest extends Pupcake\Object
{
    private $route;

    public function __construct($route)
    {
        $this->route = $route;
    }

    public function params($param_name)
    {
        $params = $this->route->getParams();
        $result = "";
        if(isset($params[$param_name])){
            $result = $params[$param_name];
        }
        return $result;

    }
}

class ExpressResponse extends Pupcake\Object
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

class Express extends Pupcake\Service
{
    public function start($app)
    {
        $app->on("system.request.found", function($route) use ($app) {
            $req = new ExpressRequest($route);
            $res = new ExpressResponse($app, $route);
            call_user_func_array($route->getCallback(), array($req, $res));
            return $route->storageGet('output');
        });
    }
}
