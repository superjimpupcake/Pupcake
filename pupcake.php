<?php

/**
 * Pupcake --- a microframework for PHP 5.3+
 *
 * @author Zike(Jim) Huang
 * @copyright 2012 Zike(Jim) Huang
 * @version 0.8.4.0
 * @package Pupcake
 */

namespace Pupcake;

class EventManager
{
    /**
     * @var array
     * Pupcake Event Queue
     */
    private $event_queue; 

    public function __construct()
    {
        $this->event_queue = array();
    }

    public static function instance()
    {
        static $instance;
        if(!isset($instance)){
            $instance = new static();
        }
        return $instance; 
    }

    public function getEventQueue()
    {
        return $this->event_queue;
    }

    public function register($event_name, $callback)
    {
        $this->event_queue[$event_name] = $callback;
    }

    public function trigger($event_name, $callback = "", $params = array())
    {
        if(isset($this->event_queue[$event_name])){
            $callback = $this->event_queue[$event_name];
        }

        if($callback == ""){
            return "";
        }
        else{
            return call_user_func_array($callback, $params);
        } 
    }
}

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

class Error
{
    private $severity;
    private $message;
    private $file_path;
    private $line;

    public function __construct($severity, $message, $file_path, $line)
    {
        $this->severity = $severity;
        $this->message = $message;
        $this->file_path = $file_path;
        $this->line = $line;
    }

    public function getSeverity()
    {
        return $this->severity;
    }

    public function getMessage()
    {
        return $this->message;
    }

    public function getFilePath()
    {
        return $this->file_path;
    }

    public function getLine()
    {
        return $this->line;
    }
}

class Route
{
    private $request_type;
    private $route_pattern;
    private $callback;
    private $route_params;

    public function __construct($request_type = "", $route_pattern, $callback="", $route_params = array())
    {
        if($route_pattern[0] != '/'){
            $route_pattern = "/".$route_pattern;
        }
        $this->request_type = $request_type;
        $this->route_pattern = $route_pattern;
        $this->callback = $callback;
        $this->route_params = $route_params;
    }

    public function setRequestType($request_type)
    {
        $this->request_type = $request_type;
    }

    public function getRequestType()
    {
        return $this->request_type;
    }

    public function setPattern($route_pattern)
    {
        $this->route_pattern = $route_pattern;
    }

    public function getPattern()
    {
        return $this->route_pattern;
    }

    public function setCallback($callback)
    {
        $this->callback = $callback;
    }

    public function getCallback(){
        return $this->callback;
    }

    public function setParams($route_params)
    {
        $this->route_params = $route_params;
    }

    public function getParams()
    {
        return $this->route_params;
    }

    public function via()
    {
        $router = Router::instance();
        $request_types = func_get_args();
        $request_types_count = count($request_types);
        if($request_types_count > 0){
            for($k=0;$k<$request_types_count;$k++){
                $this->request_type = $request_types[$k];
                $router->addRoute($this);
            } 
        }
    }
}


class Pupcake
{
    private $request_type;
    private $query_path;
    private $router;
    private $return_output;
    private $request_mode; 
    private $event_manager;
    private $components_lookup_map;

    public function __construct()
    {
        $this->event_manager = EventManager::instance();

        set_error_handler(function ($severity, $message, $file_path, $line){
            $error = new Error($severity, $message, $file_path, $line);
            EventManager::instance()->trigger('system.error.detected', '', array($error));
            return true;
        }, E_ALL);

        register_shutdown_function(function(){
            EventManager::instance()->trigger('system.shutdown');
        });


        $this->request_mode = "external"; //default request mode is external
        $this->return_output = false;
        $this->router = Router::instance();
    }

    public static function instance()
    {
        static $instance;
        if(!isset($instance)){
            $instance = new static();
        }
        return $instance; 
    }

    public function getRouter()
    {
        return $this->router;
    }

    public function map($route_pattern, $callback)
    {
        $route = new Route("", $route_pattern, $callback);
        return $route;
    }

    public function get($route_pattern, $callback)
    {
        return $this->map($route_pattern, $callback)->via('GET');
    }

    public function post($route_pattern, $callback)
    {
        return $this->map($route_pattern, $callback)->via('POST');
    }

    public function delete($route_pattern, $callback)
    {
        return $this->map($route_pattern, $callback)->via('DELETE');
    }

    public function put($route_pattern, $callback)
    {
        return $this->map($route_pattern, $callback)->via('PUT');
    }

    public function options($route_pattern, $callback)
    {
        return $this->map($route_pattern, $callback)->via('OPTIONS');
    }

    public function any($route_pattern, $callback)
    {
        return $this->map($route_pattern, $callback)->via('*');
    } 

    public function notFound($callback)
    {
        $this->event_manager->register('system.request.notfound', $callback);
    }

    public function sendInternalRequest($request_type, $query_path)
    {
        $is_nested_internal_request = false;
        if($this->request_mode == 'internal'){ //this is a nested internal request
            $is_nested_internal_request = true;
        }

        $this->setRequestMode("internal");
        $current_request_type = $_SERVER['REQUEST_METHOD'];
        $_SERVER['REQUEST_METHOD'] = $request_type; 
        $this->setQueryPath($query_path);
        $this->setReturnOutput(true);
        $output = $this->run();
        $_SERVER['REQUEST_METHOD'] = $current_request_type;
        if(!$is_nested_internal_request){
            $this->setReturnOutput(false);
            $this->setRequestMode("external");
        }

        return $output;
    }

    public function forward($request_type, $query_path)
    {
        return $this->sendInternalRequest($request_type, $query_path);
    }

    public function run()
    {
        $request_matched = $this->event_manager->trigger('system.request.routing', function(){
            $app = Pupcake::instance();
            $router = Router::instance();
            $route_map = $router->getRouteMap();
            $request_matched = false;
            if($app->getRequestMode() == 'external'){
                $query_path = "/";
                $script_base_name = basename($_SERVER['SCRIPT_FILENAME']);
                if($_SERVER['PHP_SELF'] != '/'.$script_base_name){
                    $query_path = str_replace($script_base_name."/", "", $_SERVER['PHP_SELF']);
                }
                $app->setQueryPath($query_path);
            }
            $output = "";
            if(count($route_map) > 0){
                $request_types = array($_SERVER['REQUEST_METHOD'], "*");
                foreach($request_types as $request_type){
                    if(isset($route_map[$request_type]) && count($route_map[$request_type]) > 0){
                        foreach($route_map[$request_type] as $route_pattern => $route){
                            //once we found there is a matching route, stop
                            $request_matched = EventManager::instance()->trigger('system.request.route.matching', 
                                array($router, 'processRouteMatching'), 
                                array($request_type,$app->getQueryPath(), $route_pattern)
                            );
                            if($request_matched){
                                break 2;
                            }
                        }
                    }
                }
            }

            return $request_matched;
        });

        if(!$request_matched){
            //request not found
            header("HTTP/1.0 404 Not Found");
            $output = $this->event_manager->trigger("system.request.notfound", function(){
                return "Invalid Request";
            });
        }
        else{
            //request matched
            $matched_route = $this->router->getMatchedRoute();
            $output = $this->event_manager->trigger("system.request.found", function($matched_route){
                return call_user_func_array($matched_route->getCallback(), $matched_route->getParams());
            }, array($matched_route));
        }

        if($this->return_output){
            return $output;
        }
        else{
            ob_start();
            print $output;
            $output = ob_get_contents();
            ob_end_clean();
            print $output;
        }
    }

    public function setQueryPath($query_path)
    {
        if(strlen($query_path) > 0 && $query_path[0] != '/'){
            $query_path = "/".$query_path;
        }
        $this->query_path = $query_path;
    }

    public function getQueryPath()
    {
        return $this->query_path;
    }

    public function setReturnOutput($return_output)
    {
        $this->return_output = $return_output;
    }

    public function setRequestMode($request_mode)
    {
        $this->request_mode = $request_mode; 
    }

    public function getRequestMode()
    {
        return $this->request_mode;
    }

    public function getRequestType()
    {
        return $_SERVER['REQUEST_METHOD'];
    }

    public function redirect($uri)
    {
        if($this->request_mode == 'external'){
            header("Location: ".$uri);
        }
        else if($this->request_mode == 'internal'){
            return $this->forward('GET', $uri);
        }
    }

    public function on($event_name, $callback)
    {
        $this->event_manager->register($event_name, $callback);
    }

    public function trigger($event_name)
    {
        $this->event_manager->trigger($event_name);
    }

    public function executeRoute(Route $route)
    {
        return $this->router->executeRoute($route);
    }

    public function bridge($bridge_name)
    {
        $bridge = null;
        $bridge_path = __DIR__."/bridges/".$bridge_name."/bridge.php";
        if(is_readable($bridge_path)){
            require $bridge_path;
        }
        return $bridge;
    }
}
