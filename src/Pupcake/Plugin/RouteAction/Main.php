<?php
/**
 * RouteAction plugin
 * Allow a route mapped to a specific route action, it can be either:
 * 1. a closure,  
 * 2. a string representing controller#action
 */
namespace Pupcake\Plugin\RouteAction;

use Pupcake;

class Main extends Pupcake\Plugin
{
    public function load($config = array())
    {
        $this->help("system.routing.route.create", function($event) {
            $route = $event->props('route');
            $route->method("to", function($action) use ($route, $time) {
                $route->storageSet("route_action", $action); 
                return $route; //return route for further extension
            });
            $route->method("getAction", function() use ($route, $time) {
                return $route->storageGet("route_action");
            });
            return $route;
        });
    }
}
