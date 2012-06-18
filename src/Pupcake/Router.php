<?php
namespace Pupcake;

class Router extends Object
{
    private $app;
    private $route_map;
    private $route_request_types;
    private $route_not_found_handler;
    private $matched_route;

    public function __construct()
    {
        $this->route_map = array(); //initialize the route map
        $this->route_request_types = array();
    }

    public function belongsTo($app)
    {
        $this->app = $app;
    }

    public function getAppInstance()
    {
        return $this->app;
    }

    public function setMatchedRoute(Route $matched_route)
    {
        $this->matched_route = $matched_route;
    }

    public function getMatchedRoute()
    {
        return $this->matched_route;
    }

    public function addRoute($route)
    {
        $request_type = $route->getRequestType();
        $route_pattern = $route->getPattern();
        if(!isset($this->route_map[$request_type])){
            $this->route_map[$request_type] = array();
        }

        $this->route_map[$request_type][$route_pattern] = $route;
    }

    public function getRoute($request_type, $route_pattern)
    {
        if($route_pattern[0] != '/'){
            $route_pattern = '/'.$route_pattern;
        }
        $request_type = strtoupper($request_type);
        return $this->route_map[$request_type][$route_pattern];
    }

    public function getRouteMap()
    {
        return $this->route_map;
    }

    public function normalize($query_path)
    {
        if(strlen($query_path) > 0 && $query_path[0] != '/'){
            $query_path = "/".$query_path;
        }
        else if($query_path == ""){
            $query_path = "/";
        }

        return $query_path;
    }

    /**
     * check if route exists
     */
    public function routeExists($request_type, $route_pattern, $query_path)
    {
        $matched = $this->app->trigger(
            'system.request.route.matching', 
            array($this, 'processRouteMatching'),
            array(
                'request_type'=> $request_type, 
                'query_path' => $query_path,
                'route_pattern' => $route_pattern
            )
        );

        return $matched;
    } 

    /**
     * find a route
     */
    public function findMatchedRoute($request_method = "", $query_path = "", $route_map)
    {
        $query_path = $this->normalize($query_path);

        $request_matched = false;
        $output = "";
        if(count($route_map) > 0){
            $request_types = array_keys($route_map);
            $request_types_to_lookup = array();
            foreach($request_types as $request_type){
                if($request_type == $request_method || $request_type == "*"){
                    $request_types_to_lookup[] = $request_type;
                }
            }
            foreach($request_types_to_lookup as $request_type){
                if(isset($route_map[$request_type]) && count($route_map[$request_type]) > 0){
                    foreach($route_map[$request_type] as $route_pattern => $route){
                        //once we found there is a matched route, stop
                        $matched = $this->routeExists($request_type, $route_pattern, $query_path);
                        if($matched){
                            $request_matched = true;
                            break 2;
                        }
                    }
                }
            }
        }

        return $request_matched;
    }

    /**
     * process route matching
     * @param Event the event object
     * @return boolean whether the route matched the uri or not
     */
    public function processRouteMatching($event)
    {
        $request_type = $event->props('request_type');
        $uri = $event->props('query_path');
        $route_pattern= $event->props('route_pattern');
        $result = false;
        $params = array();

        $route_pattern_length = strlen($route_pattern);
        $path_pos = strpos($route_pattern, "*path"); //see if there is *path exists
        if($path_pos !== FALSE){
            $first_part_of_path = substr($route_pattern, 0, $path_pos);
            if(substr($uri, 0, $path_pos) == $first_part_of_path){
                $params[":path"] = str_replace($first_part_of_path, "", $uri);
                $route = $this->getRoute($request_type, $route_pattern);
                $route->setParams($params);
                $this->setMatchedRoute($route); 
                $result = true;
                return $result;
            }
        }

        $uri_comps = explode("/", $uri);
        $uri_comps_count = count($uri_comps);
        $route_pattern_comps = explode("/", $route_pattern);
        $route_pattern_comps_count = count($route_pattern_comps);
        if($uri_comps_count == $route_pattern_comps_count){
            for($k=1;$k<$route_pattern_comps_count;$k++){ //we should start from index 1 since index 0 is the /
                if($route_pattern_comps[$k][0] == ":"){
                    $token = $route_pattern_comps[$k];
                    $params[$token] = $uri_comps[$k];
                    $route_pattern_comps[$k] = "";
                    $uri_comps[$k] = "";
                }
            }

            $uri_reformed = implode("/",$uri_comps);
            $route_pattern_reformed = implode("/",$route_pattern_comps);
            $route = $this->getRoute($request_type, $route_pattern);
            $route->setParams($params);

            if($uri_reformed == $route_pattern_reformed){
                $results = $this->app->trigger("system.routing.route.matched", "", array('route' => $route));

                //the result can be either a boolean or an array 
                $result = true;
                if( is_array($results) && count($results) > 0 ){  //the result is an array
                    foreach($results as $matched){
                        if(!$matched){
                            $result = false;
                            break;
                        }
                    }
                }
                else if($results === FALSE){
                    $result = false; 
                }

                if($result){ 
                    $this->setMatchedRoute($route); 
                }

            }
        }

        return $result;
    }

    /**
     * Execute a route
     * @param route object
     */
    public function executeRoute($route, $params = array())
    {
        return $route->execute($params);
    }

}
