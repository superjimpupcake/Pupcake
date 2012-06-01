<?php
namespace Pupcake;

class Router
{
    private $route_map;
    private $route_not_found_handler;
    private $matched_route;

    public function __construct()
    {
        $this->route_map = array(); //initialize the route map
    }

    public static function instance()
    {
        static $instance;
        if(!isset($instance)){
            $instance = new static();
        }
        return $instance; 
    }

    public function setMatchedRoute(Route $matched_route)
    {
        $this->matched_route = $matched_route;
    }

    public function getMatchedRoute()
    {
        return $this->matched_route;
    }

    public function addRoute(Route $route)
    {
        $request_type = $route->getRequestType();
        $route_pattern = $route->getPattern();

        if($route_pattern == "/*"){
            $route_pattern = "/:path";
        }

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

    public function processRouteMatching($request_type, $uri, $route_pattern)
    {
       return $this->matches($request_type, $uri, $route_pattern); 
    }

    /**
     * Match URI
     *
     * Parse this route's pattern, and then compare it to an HTTP resource URI
     * This method was modeled after the techniques demonstrated by Dan Sosedoff at:
     *
     * http://blog.sosedoff.com/2009/09/20/rails-like-php-url-router/
     *
     * @param   string  $request_type The request type
     * @param   string  $uri A Request URI
     * @param   string  $route_pattern The route pattern
     * @return  bool
     */
    protected function matches( $request_type, $uri, $route_pattern ) 
    {
        $params = array(); //clear possible previous matched params
        //Extract URL params
        preg_match_all('@:([\w]+)@', $route_pattern, $param_names, PREG_PATTERN_ORDER);
        $param_names = $param_names[0];

        //Convert URL params into regex patterns, construct a regex for this route

        $matching_callback = function($matches){
            $key = str_replace(':', '', $matches[0]);
            return '(?P<' . $key . '>[a-zA-Z0-9_\-\.\!\~\*\\\'\(\)\:\@\&\=\$\+,%]+)';
        };

        $pattern_as_regex = preg_replace_callback('@:[\w]+@', $matching_callback,  $route_pattern);
        
        if ( substr($route_pattern, -1) === '/' ) {
            $pattern_as_regex = $pattern_as_regex . '?';
        }
        $pattern_as_regex = '@^' . $pattern_as_regex . '$@';

        //Cache URL params' names and values if this route matches the current HTTP request
        if ( preg_match($pattern_as_regex, $uri, $param_values) ) {
            array_shift($param_values);
            foreach ( $param_names as $index => $value ) {
                $val = substr($value, 1);
                if ( isset($param_values[$val]) ) {
                    $params[$val] = urldecode($param_values[$val]);
                }
            }

            $route = $this->getRoute($request_type, $route_pattern);
            $route->setParams($params);
            $this->setMatchedRoute($route); 

            return true;
        } else {
            return false;
        }
    }

    public function executeRoute($route)
    {
        return call_user_func_array($route->getCallback(), $route->getParams());
    }
}

