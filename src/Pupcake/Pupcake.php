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
    private $event_queue;
    private $event_execution_result;
    private $services; //holding an array of services
    private $service_loading; //see if the service is loading or not
    private $services_started; //tell the system to see if the services are started or not
    private $events_services_map; //the event => services mapping
    private $events_services_map_processed;

    public function __construct()
    {
        $this->services = array();
        $this->events_services_map = array();
        $this->events_services_map_processed = false;
        $this->services_started = false;
        $this->service_loading = false;
        if(!isset($_SERVER['PATH_INFO'])){
            $_SERVER['PATH_INFO'] = "/";
        }
        $this->query_path = $_SERVER['PATH_INFO'];
        
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

    public function handleError($severity, $message, $file_path, $line)
    {
        $error = new Error($severity, $message, $file_path, $line);
        $this->trigger('system.error.detected', '', array('error' => $error));
    }

    public function handleShutdown()
    {
        $this->trigger('system.shutdown');
    }

    public function map($route_pattern, $callback = "")
    {
        //start the services, only once
        if(!$this->services_started){
            $this->startServices();
            $this->services_started = true;
        }

        $route = new Route();
        $route->belongsTo($this->router); #route belongs to router
        $route->setRequestType("");
        $route->setPattern($route_pattern);
        $route->setCallback($callback);
        $this->trigger('system.routing.route.create', '', array('route' => $route));
        return $route;
    }

    public function get($route_pattern, $callback = "")
    {
        return $this->map($route_pattern, $callback)->via('GET');
    }

    public function post($route_pattern, $callback = "")
    {
        return $this->map($route_pattern, $callback)->via('POST');
    }

    public function delete($route_pattern, $callback = "")
    {
        return $this->map($route_pattern, $callback)->via('DELETE');
    }

    public function put($route_pattern, $callback = "")
    {
        return $this->map($route_pattern, $callback)->via('PUT');
    }

    public function options($route_pattern, $callback = "")
    {
        return $this->map($route_pattern, $callback)->via('OPTIONS');
    }

    public function patch($route_pattern, $callback = "")
    {
        return $this->map($route_pattern, $callback)->via('PATCH');
    }

    public function any($route_pattern, $callback = "")
    {
        return $this->map($route_pattern, $callback)->via('*');
    } 

    public function notFound($callback)
    {
        $this->on('system.request.notfound', $callback);
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

    public function startServices()
    {
        //register all events in the event service map, only happen once
        if(!$this->events_services_map_processed){
            if(count($this->events_services_map) > 0){
                foreach($this->events_services_map as $event_name => $services){
                    if(count($services) > 0){
                        $this->service_loading = true;
                        $this->on($event_name, function($event) use ($services) {
                            return call_user_func_array(array($event, "register"), $services)->start();
                        });
                        $this->service_loading = false;
                    }
                }
                $this->events_services_map_processed = true;
            }
        }
    }

    public function run()
    {
        $app = $this; //use the current app instance
        $route_map = $app->getRouter()->getRouteMap();
        $request_matched = $this->trigger('system.request.routing', function() use($app){ #pass dependency, app
            $route_map = $app->getRouter()->getRouteMap();
            $request_matched = false;
            $output = "";
            if(count($route_map) > 0){
                $request_types = array($_SERVER['REQUEST_METHOD'], "*");
                foreach($request_types as $request_type){
                    if(isset($route_map[$request_type]) && count($route_map[$request_type]) > 0){
                        foreach($route_map[$request_type] as $route_pattern => $route){
                            //once we found there is a matching route, stop
                            $request_matched = $app->trigger(
                                'system.request.route.matching', 
                                array($app->getRouter(), 'processRouteMatching'),
                                array(
                                    'request_type'=> $request_type, 
                                    'query_path' => $app->getQueryPath(),
                                    'route_pattern' => $route_pattern
                                )
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

        $output = "";
        $return_outputs = array();
        if(!$request_matched){
            $output = $this->trigger("system.request.notfound", function(){
                //request not found
                header("HTTP/1.1 404 Not Found");
                return "Invalid Request";
            });
        }
        else{
            //request matched
            $output = $this->trigger("system.request.found", 
                function($event){
                    return $event->props('route')->execute();
                },
                    array('route' => $this->router->getMatchedRoute())
                );
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

    /**
     * add a callback to the event, one event, one handler callback
     * the handler callback is swappable, so later handler callback can override previous handler callback
     */
    public function on($event_name, $handler_callback)
    {
        if(!$this->service_loading){
            //start the services, only once
            if(!$this->services_started){
                $this->startServices();
                $this->services_started = true;
            }
        }

        $event = null;
        if(!isset($this->event_queue[$event_name])){
            $event = new Event($event_name);
            $this->event_queue[$event_name] = $event;
        }

        $event = $this->event_queue[$event_name];
        $event->setHandlerCallback($handler_callback);
    }

    public function trigger($event_name, $handler_callback = "", $event_properties = array())
    {
        $event = null;
        if(isset($this->event_queue[$event_name])){
            $event = $this->event_queue[$event_name];
            $event->setProperties($event_properties);

            $handler_callback = $event->getHandlerCallback();
            if(is_callable($handler_callback)){
                $result = call_user_func_array($handler_callback, array($event));
                $event->setHandlerCallbackReturnValue($result);
            }
            else if( is_callable($handler_callback) ){
                $event->setHandlerCallback($handler_callback);
                $result = call_user_func_array($handler_callback, array($event));
                $event->setHandlerCallbackReturnValue($result);
            }
        }
        else{
            $event = new Event($event_name);
            $event->setProperties($event_properties);
            if(is_callable($handler_callback)){
                $event->setHandlerCallback($handler_callback);
                $result = call_user_func_array($handler_callback, array($event));
                $event->setHandlerCallbackReturnValue($result);
                $this->event_queue[$event_name] = $event;
            }
        }

        $result = $event->getHandlerCallbackReturnValue();

        return $result;
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
        if(!isset($this->services[$service_name])){
            $this->services[$service_name] = new $service_name();
            $this->services[$service_name]->setContext(new ServiceContext($this));
            $this->services[$service_name]->setName($service_name);
            $this->services[$service_name]->start($config); //start the service

            //now preload all service handlers to the event queue

            $event_handlers = $this->services[$service_name]->getEventHandlers();
            if(count($event_handlers) > 0){
                foreach($event_handlers as $event_name => $callback){
                    if(!isset($this->events_services_map[$event_name])){
                        $this->events_services_map[$event_name] = array();
                    }
                    $this->events_services_map[$event_name][] = $this->services[$service_name]; //add the service object to the map
                }
            }
        }
        return $this->services[$service_name];
    }
}
