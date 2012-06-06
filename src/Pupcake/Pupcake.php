<?php
/**
 * Pupcake --- a microframework for PHP 5.3+
 *
 * @author Zike(Jim) Huang
 * @copyright 2012 Zike(Jim) Huang
 * @version 1.0
 * @package Pupcake
 */

namespace Pupcake;

class Object
{

    private $methods;

    public static function instance()
    {
        static $instance;
        if(!isset($instance)){
            $instance = new static();
        }
        return $instance; 
    }

    public function __call($method_name, $params)
    {
        $class_name = get_class($this);
        if(isset($this->methods[$class_name])){
           return call_user_func_array($this->methods[$class_name], $params); 
        }
    }

    final public function method($name, $callback)
    {
        $class_name = get_called_class(); 
        $this->methods[$class_name] = $callback;
    }
}

class EventManager extends Object
{
    /**
     * @var array
     * Pupcake Event Queue
     */
    private $event_queue; 
    
    /**
     * @var array
     * @Pupcake Event execution result
     */
    private $event_execution_result;

    public function __construct()
    {
        $this->event_queue = array();
        $this->event_execution_result = array();
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

        if($callback == "" && isset($this->event_execution_result[$event_name]) ){
            return $this->event_execution_result[$event_name];
        }
        else{
            if(isset($this->event_queue[$event_name])){
                $callback = $this->event_queue[$event_name];
            }

            $result = "";
            if($callback != ""){
                $result = call_user_func_array($callback, $params);
                $this->event_execution_result[$event_name] = $result;
            } 
            return $result;
        }
    }
}

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
                        return true;
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

class Error extends Object
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

class Route extends Object
{
    private $request_type;
    private $route_pattern;
    private $callback;
    private $route_params;

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
        if($route_pattern[0] != '/'){
            $route_pattern = "/".$route_pattern;
        }

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

        return $this; # return the route instance to allow future extension
    }
}


class Pupcake extends Object
{
    private $request_type;
    private $query_path;
    private $router;
    private $return_output;
    private $request_mode; 
    private $event_manager;

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

    public function setRoutePrototype($route_prototype)
    {
        $this->route_prototype = $route_prototype;
    }

    public function getRoutePrototype()
    {
        return $this->route_prototype;
    }

    public function getRouter()
    {
        return $this->router;
    }

    public function map($route_pattern, $callback)
    {
        $route = $this->event_manager->trigger('system.routing.route.create', function(){
            return new Route();
        });
        $route->setRequestType("");
        $route->setPattern($route_pattern);
        $route->setCallback($callback);
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

    public function patch($route_pattern, $callback)
    {
        return $this->map($route_pattern, $callback)->via('PATCH');
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
        if($this->getRequestMode() == 'internal'){ //this is a nested internal request
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
        $app = $this; //use the current app instance
        $router = $this->router; //use the current router
        $request_matched = EventManager::instance()->trigger('system.request.routing', function() use($app, $router){
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
            $output = $this->event_manager->trigger("system.request.notfound", function(){
                //request not found
                header("HTTP/1.0 404 Not Found");
                return "Invalid Request";
            });
        }
        else{
            //request matched
            $output = $this->event_manager->trigger("system.request.found", function($matched_route){
                return call_user_func_array($matched_route->getCallback(), $matched_route->getParams());
            }, array(Router::instance()->getMatchedRoute()));
        }

        if($this->isReturnOutput()){
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

    public function isReturnOutput()
    {
        return $this->return_output;
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
        if($this->getRequestMode() == 'external'){
            header("Location: ".$uri);
        }
        else if($this->getRequestMode() == 'internal'){
            return $this->forward('GET', $uri);
        }
    }

    public function on($event_name, $callback)
    {
        $this->event_manager->register($event_name, $callback);
    }

    public function trigger($event_name)
    {
        return $this->event_manager->trigger($event_name);
    }

    public function executeRoute(Route $route)
    {
        return $this->router->executeRoute($route);
    }
}
