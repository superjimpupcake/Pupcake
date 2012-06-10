<?php
/**
 * ConstraintRoute Service
 */
namespace Pupcake\Service;

use Pupcake;

class RouteConstraint extends Pupcake\Service
{
    public function start($app, $config = array())
    {
        /**
         * When a route object is being created, we add the constraint method 
         * to it and store the constraint into this route object's storage
         */
        $app->on("system.routing.route.create", function($route){
            $route->method('constraint', function($constraint) use($route){
                $route->storageSet('constraint', $constraint);
                return $route; //return the route reference for futher extension
            });
        });

        /**
         * When a route object is initially matched, we add further checking logic 
         * to make sure the constraint is applying toward the route matching process
         */
        $app->on("system.routing.route.matched", function($route){
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
