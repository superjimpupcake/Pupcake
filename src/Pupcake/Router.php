<?php
namespace Pupcake;

class Router extends Object
{
    private $route_map;
    private $route_not_found_handler;
    private $matched_route;

    public function __construct()
    {
        $this->route_map = array(); //initialize the route map
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
        return $this->route_map[$request_type][$route_pattern];
    }

    public function getRouteMap()
    {
        return $this->route_map;
    }

    /**
     * process route matching
     * @param string the request type
     * @param string the uri
     * @param the route pattern
     * @return boolean whether the route matched the uri or not
     */
    public function processRouteMatching($request_type, $uri, $route_pattern)
    {

        $result = false;
        $params = array();

        if( ($request_type == $_SERVER['REQUEST_METHOD'] || $request_type == '*') && $route_pattern == '/:path'){
            $result = true;
            $params = array('path' => $uri);
        }
        else{
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

                if($uri_reformed == $route_pattern_reformed){
                    $route = $this->getRoute($request_type, $route_pattern);
                    $route->setParams($params);
                    $result = EventManager::instance()->trigger("system.routing.route.matched", function($route){
                        return $route->matched();
                    }, array($route));
                    if($result){
                        if(count($params) > 0){
                            foreach($params as $name => $val){
                                unset($params[$name]);
                                $name = str_replace(":","",$name);
                                if($val[0] == '/'){
                                    $val[0] = '';
                                    $val = trim($val);
                                }
                                $params[$name] = $val;
                            }
                        }

                        $route->setParams($params);
                        $this->setMatchedRoute($route); 
                    }
                }
            }
        }

        return $result;
    }

    public function executeRoute($route)
    {
        return call_user_func_array($route->getCallback(), $route->getParams());
    }
}

