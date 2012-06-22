<?php
/**
 * RouteConstraint Plugin
 */
namespace Pupcake\Plugin\RouteConstraint;

use Pupcake;

class Main extends Pupcake\Plugin
{
    public function load($config = array())
    {
        /**
         * When a route object is being created, we add the constraint method 
         * to it and store the constraint into this route object's storage
         */
        $this->help("system.routing.route.create", function($event){
            $route = $event->props('route');
            $route->method('constraint', function($constraint) use($route){
                $route->storageSet('constraint', $constraint);
                return $route; //return the route reference for futher extension
            });
        });

        /**
         * When a route object is initially matched, we add further checking logic 
         * to make sure the constraint is applying toward the route matching process
         */
        $this->help("system.routing.route.matched", function($event){
            $route = $event->props('route');
            $matched = true;
            $params = $route->getParams();
            $constraint = $route->storageGet('constraint');
            if(count($constraint) > 0){
                foreach($constraint as $token => $validation_callback){
                    if(is_callable($validation_callback)){
                        if(!$validation_callback($params[$token])){
                            $matched = false;
                            break;
                        }
                    }
                }
            } 
            return $matched;
        });        
    }
}
