<?php

/**
 * Pupcake --- a microframework for PHP 5.3+
 *
 * @author Zike(Jim) Huang
 * @copyright 2012 Zike(Jim) Huang
 * @version 0.5.1
 * @package Pupcake
 */

namespace Pupcake;

class Pupcake
{
    private $request_type;
    private $query_path;
    private $router;
    private $return_output;
    private $request_mode; 
    private $event_manager;
    private $class_loader;

    public function __construct()
    {
        set_error_handler(function ($severity, $message, $filepath, $line){
            EventManager::instance()->trigger('system.error.detected', '', func_get_args());
            return true;
        }, E_ALL);

        register_shutdown_function(function(){
            EventManager::instance()->trigger('system.shutdown');
        });

        $this->class_loader = $this->getClassLoader();

        $this->request_mode = "external"; //default request mode is external
        $this->return_output = false;
        $this->router = Router::instance();
        $this->event_manager = EventManager::instance();
    }

    public static function instance()
    {
        static $instance;
        if(!isset($instance)){
            $instance = new static();
        }
        return $instance; 
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
        $route_map = $this->router->getRouteMap();
        $request_matched = false;
        if($this->request_mode == 'external'){
            $query_path = "/";
            $script_base_name = basename($_SERVER['SCRIPT_FILENAME']);
            if($_SERVER['PHP_SELF'] != '/'.$script_base_name){
                $query_path = str_replace($script_base_name."/", "", $_SERVER['PHP_SELF']);
            }
            $this->setQueryPath($query_path);
        }
        $output = "";
        if(count($route_map) > 0){
            $request_types = array($_SERVER['REQUEST_METHOD'], "*");
            foreach($request_types as $request_type){
                if(isset($route_map[$request_type]) && count($route_map[$request_type]) > 0){
                    foreach($route_map[$request_type] as $route_pattern => $route){
                        //once we found there is a matching route, stop
                        if($this->router->processRouteMatching($request_type, $this->query_path, $route_pattern)){
                            $request_matched = true;
                            break 2;
                        }
                    }
                }
            }
        }

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

    public function setReturnOutput($return_output)
    {
        $this->return_output = $return_output;
    }

    public function setRequestMode($request_mode)
    {
        $this->request_mode = $request_mode; 
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

    public function executeRoute(Route $route)
    {
        return $this->router->executeRoute($route);
    }

    /**
     * get a universal class loader
     */
    public function getClassLoader()
    {
        static $loader;
        if(!isset($loader)){
            require __DIR__."/components/Symfony/Component/ClassLoader/UniversalClassLoader.php";
            $loader = new \Symfony\Component\ClassLoader\UniversalClassLoader();
            $loader->registerNamespaces(array(
                'Pupcake' => __DIR__."/components",
            ));
            $loader->register();

            /**
             * register more namesapces
             */
            $namespaces = EventManager::instance()->trigger('system.namespace.register', function(){
                return array();
            });
            if(count($namespaces) > 0){
                $loader->registerNamespaces($namespaces);
                $loader->register();
            }
        }
        return $loader;
    }
}
