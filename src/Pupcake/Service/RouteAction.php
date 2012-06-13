<?php
/**
 * RouteAction service
 * Allow a route mapped to a specific route action, it can be either:
 * 1. a closure,  
 * 2. a string representing controller#action
 */
namespace Pupcake\Service;

use Pupcake;

class RouteAction extends Pupcake\Service
{
    public function start($config = array())
    {
        $this->on("system.routing.route.create", function($event){
            $time = time(); //use time to make the variable stored behaves like a private variable
            $route = $event->props('route');
            $route->method("to", function($action) use ($route, $time) {
                $route->storageSet($time."_route_action", $action); 
                return $route; //return route for further extension
            });
            $route->method("getAction", function() use ($route, $time) {
                return $route->storageGet($time."_route_action");
            });
        });
    }
}
