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

            $matched_route = null;

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

            $request_types = array_keys($route_map);
            $request_types_to_lookup = array();
            foreach($request_types as $request_type){
                if($request_type == $_SERVER['REQUEST_METHOD'] || $request_type == "*"){
                    $request_types_to_lookup[] = $request_type;
                }
                else{
                    unset($route_map[$request_type]); //remove other uneccessary routes
                }
            }

            foreach($request_types_to_lookup as $request_type){
                if(isset($route_map[$request_type]) && count($route_map[$request_type]) > 0){
                    foreach($route_map[$request_type] as $route_pattern => $route){
                        if($matched_route === NULL){ // find the match route, only once
                            if($route->getPattern() == "/*path"){
                                $matched_route = $route;
                                $params = array('path' => $_REQUEST['PATH_INFO']);
                            }
                            else{
                                $current_route_pattern_comps = explode("/", $current_route_pattern);
                                $current_route_pattern_comps_count = count($current_route_pattern_comps);
                                $route_pattern_comps = explode("/", $route_pattern);
                                $route_pattern_comps_count = count($route_pattern_comps);

                                $matched = false;
                                if($current_route_pattern_comps_count == $route_pattern_comps_count){
                                    for($k=0;$k<$current_route_pattern_comps_count; $k++){
                                        if($current_route_pattern_comps[$k][0] == ":"){
                                            $current_route_pattern_comps[$k][0] = "";
                                            unset($current_route_pattern_comps[$k]);
                                            unset($route_pattern_comps[$k]);
                                        }
                                    }
                                    $current_route_pattern_reformed = implode("/", $current_route_pattern);
                                    $route_pattern_reformed = implode("/", $route_pattern);
                                    if($current_route_pattern_reformed == $route_pattern_reformed){
                                        $matched = true;
                                    }
                                }

                                if($matched){
                                    $matched_route = $route;
                                    break 2;
                                }
                            }
                        }
                    }
                }
            }

            $service->setRouteMapToLookup($route_map);

            $output = ""; //return empty response by default

            if($matched_route !== NULL){ //we found the route
                $params = array();
                $route_pattern_comps = explode("/", $matched_route->getPattern());
                $route_pattern_comps_count = count($route_pattern_comps);
                for($k=0;$k<$route_pattern_comps_count;$k++){
                    if($route_pattern_comps[$k][0] == ":"){
                        $route_pattern_comps[$k][0] = "";
                        $token = trim($route_pattern_comps[$k]);
                        $params[$token] = $service->getUrlComponent($k);
                    }
                }
                $matched_route->setParams($params);
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

    public function setUrlComponent($url_component)
    {
        $this->url_component = $url_component;
    }

    public function getUrlComponent($index)
    {
        return $this->url_component[$index];
    }

    public function start($config = array())
    {
        $service = $this;
        $this->on("system.request.found", function($event) use ($service) {
            $route = $event->props('route');
            $query_path = $_SERVER['PATH_INFO'];
            $service->setUrlComponent(explode("/", $query_path));
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
