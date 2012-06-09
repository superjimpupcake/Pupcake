<?php
/**
 * Pupcake --- a microframework for PHP 5.3+
 *
 * @author Zike(Jim) Huang
 * @copyright 2012 Zike(Jim) Huang
 * @package Pupcake
 */

namespace Pupcake;

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
        $this->query_path = $_SERVER['PATH_INFO'];
        $this->event_manager = new EventManager();
        $this->event_manager->belongsTo($this);
        
        set_error_handler(array($this, 'handleError'), E_ALL);
        register_shutdown_function(array($this, 'handleShutdown'));

        $this->request_mode = "external"; //default request mode is external
        $this->return_output = false;
        $this->router = new Router(); #initiate one router for this app instance
        $this->router->belongsTo($this);
    }

    public function getRouter()
    {
        return $this->router;
    }

    public function getEventManager()
    {
        return $this->event_manager;
    }

    public function handleError($severity, $message, $file_path, $line)
    {
        $error = new Error($severity, $message, $file_path, $line);
        $this->event_manager->trigger('system.error.detected', '', array($error));
    }

    public function handleShutdown()
    {
        $this->event_manager->trigger('system.shutdown');
    }

    public function map($route_pattern, $callback)
    {
        $route = $this->event_manager->trigger('system.routing.route.create', function(){
            return new Route();
        });
        $route->belongsTo($this->router); #route belongs to router
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
        $request_matched = $this->event_manager->trigger('system.request.routing', function() use($app){ #pass dependency, app
            $route_map = $app->getRouter()->getRouteMap();
            $request_matched = false;
            $output = "";
            if(count($route_map) > 0){
                $request_types = array($_SERVER['REQUEST_METHOD'], "*");
                foreach($request_types as $request_type){
                    if(isset($route_map[$request_type]) && count($route_map[$request_type]) > 0){
                        foreach($route_map[$request_type] as $route_pattern => $route){
                            //once we found there is a matching route, stop
                            $request_matched = $app->getEventManager()->trigger('system.request.route.matching', 
                                array($app->getRouter(), 'processRouteMatching'), 
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
                return $matched_route->execute();
            }, array($this->router->getMatchedRoute()));
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

    public function executeRoute($route, $params = array())
    {
        return $this->router->executeRoute($route, $params);
    }

    /**
     * get a pupcake service
     */
    public function getService($service_name, $config = array())
    {
        static $services = array();
        if(!isset($services[$service_name])){
            $service_instance = new $service_name();
            $services[$service_name] = $service_instance;
            $services[$service_name]->start($this, $config); //start the service
        }
        return $services[$service_name];
    }
}
