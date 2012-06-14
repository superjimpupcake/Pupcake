<?php
namespace Pupcake;

/**
 * The service context, it only contains certain methods from the main application
 */
class ServiceContext
{
    private $app; //the application instance

    public function __construct($app)
    {
       $this->app = $app; 
    }

    public function redirect($uri)
    {
        return $this->app->redirect($uri);
    }

    public function forward($request_type, $uri)
    {
        return $this->app->forward($request_type, $uri);
    }

    public function getMatchedRoute()
    {
        return $this->app->getRouter()->getMatchedRoute();
    }

    public function getRoute($request_type, $route_pattern)
    {
        return $this->app->getRouter()->getRoute($request_type, $route_pattern);
    }

    public function getRouteMap()
    {
        return $this->app->getRouter()->getRouteMap();
    }

    public function executeRoute($route, $params = array())
    {
        return $this->app->getRouter()->executeRoute($route);
    }

    public function getQueryPath()
    {
        return $this->app->getQueryPath();
    }
}
