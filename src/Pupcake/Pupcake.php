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
    private $router;
    private $event_queue;
    private $request_mode;
    private $query_path;
    private $plugins; //all plugins in the application
    private $plugin_loading;
    private $plugins_loaded;
    private $events_helpers;

    public function __construct()
    {
        $this->event_queue = array();
        $this->trigger("system.initialize", array($this, 'initialize'));
    }

    public function initialize(){
        $this->events_helpers = array();
        $this->plugin_loading = false;
        $this->plugins_loaded = false;

        set_error_handler(array($this, 'handleError'), E_ALL);
        register_shutdown_function(array($this, 'handleShutdown'));

        $this->router = new Router(); #initiate one router for this app instance
        $this->router->belongsTo($this);

        //use express plugin by default
        $this->usePlugin("Pupcake\Plugin\Express"); //load Express Plugin by default
    }

    /**
     * add a callback to the event, one event, one handler callback
     * the handler callback is swappable, so later handler callback can override previous handler callback
     */
    public function on($event_name, $handler_callback)
    {
        if(!$this->plugin_loading){
            //load all plugins, only once
            if(!$this->plugins_loaded){
                $this->loadAllPlugins();
                $this->plugins_loaded = true;
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

    /**
     * tell the sytem to use a plugin
     */
    public function usePlugin($plugin_name, $config = array())
    {
        if(!isset($this->plugins[$plugin_name])){
            $plugin_name = str_replace(".", "\\", $plugin_name);
            //allow plugin name to use . sign
            $plugin_class_name = $plugin_name."\Main";
            $this->plugins[$plugin_name] = new $plugin_class_name();
            $this->plugins[$plugin_name]->setAppInstance($this);
            $this->plugin_loading = true;
            $this->plugins[$plugin_name]->load($config);
            $this->plugin_loading = false;
        }
    }

    /**
     * get a plugin object
     */
    public function getPlugin($plugin_name)
    {
        if(isset($this->plugins[$plugin_name])){
            return $this->plugins[$plugin_name]['obj'];
        }
    }

    /**
     * load all plugins
     */
    public function loadAllPlugins()
    {
        if(count($this->plugins) > 0){
            foreach($this->plugins as $plugin_name => $plugin){
                $event_helpers = $plugin->getEventHelperCallbacks();
                if(count($event_helpers) > 0){
                    foreach($event_helpers as $event_name => $callback){
                        if(!isset($this->events_helpers[$event_name])){
                            $this->events_helpers[$event_name] = array();
                        }
                        $this->events_helpers[$event_name][] = $plugin; //add the plugin object to the map
                    }
                }
            }

            //register all event helpers
            if(count($this->events_helpers) > 0){
                foreach($this->events_helpers as $event_name => $plugin_objs){
                    if(count($plugin_objs) > 0){
                        $this->plugin_loading = true;
                        $this->on($event_name, function($event) use ($plugin_objs) {
                            return call_user_func_array(array($event, "register"), $plugin_objs)->start();
                        });
                        $this->plugin_loading = false;
                    }
                }
            }
        }
    }

    public function run()
    {
        $output = $this->sendRequest("external", $_SERVER['REQUEST_METHOD'], $_SERVER['PATH_INFO'], $this->router->getRouteMap());
        ob_start();
        print $output;
        $output = ob_get_contents();
        ob_end_clean();
        print $output;
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
        //load all plugins, only once
        if(!$this->plugins_loaded){
            $this->loadAllPlugins();
            $this->plugins_loaded = true;
        }

        $route = new Route();
        $route->belongsTo($this->router); #route belongs to router
        $route->setRequestType("");
        $route->setPattern($route_pattern);
        $route->setCallback($callback);
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

    public function sendRequest($request_mode, $request_type, $query_path, $route_map)
    {
        $this->query_path = $query_path;
        $this->request_mode = $request_mode;
        $request_matched = $this->router->findMatchedRoute($request_type, $query_path, $route_map);
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

        return $output;
    }

    public function getQueryPath()
    {
        return $this->query_path;
    }

    public function forward($request_type, $query_path)
    {
        $request_type = strtoupper($request_type);
        return $this->sendRequest("internal", $request_type, $query_path, $this->router->getRouteMap());
    }

    public function executeRoute($route, $params = array())
    {
        return $this->router->executeRoute($route, $params);
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

}
