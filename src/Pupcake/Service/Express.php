<?php
/**
 * Express Service
 */
namespace Pupcake\Service;

use Pupcake;

class Express extends Pupcake\Service
{

    private $route_map;
    private $url_component;

    public function __construct()
    {
        $this->route_map = null;
    }

    public function setRouteMapToLookup($route_map)
    {
        $this->route_map = $route_map;
    }

    public function getRouteMapToLookup()
    {
        return $this->route_map;
    }

    public function getNextRouteFinder($route, $req, $res)
    {
        $service = $this;
        $next = function() use ($route, $req, $res, $service) { //find the next matching route

            $route_map = array();

            if($service->getRouteMapToLookup() === NULL){
                $route_map = $service->getContext()->getRouteMap();
            }
            else{
                $route_map = $service->getRouteMapToLookup();
            }

            $current_route = $route;
            $current_route_request_type = $current_route->getRequestType();
            $current_route_pattern = $current_route->getPattern();

            //unset the current route 
            unset($route_map[$current_route_request_type][$current_route_pattern]);

            $service->setRouteMapToLookup($route_map);

            $output = ""; //return empty response by default

            $request_matched = $this->getContext()->findMatchedRoute($_SERVER['REQUEST_METHOD'], $_SERVER['PATH_INFO'], $route_map);

            $matched_route = $service->getContext()->getMatchedRoute();
            if($matched_route !== NULL){ //we found the route
                $req->setRoute($matched_route);
                $next = $service->getNextRouteFinder($matched_route, $req, $res);
                if(is_callable($next)){
                    $output = $matched_route->execute(array($req, $res, $next)); //execute route and override params
                }
            }

            return $output;

        };

        return $next;
    }

    public function start($config = array())
    {
        $service = $this;
        $this->on("system.request.found", function($event) use ($service) {
            $route = $event->props('route');
            $req = new Express\Request($route);
            $res = new Express\Response($service, $route);
            $next = $service->getNextRouteFinder($route, $req, $res);
            if(is_callable($next)){
                $route->execute(array($req, $res, $next)); //execuite route and override params
            }
            return $route->storageGet('output');
        });
    }
}
