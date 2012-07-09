<?php
namespace Pupcake;

class Route extends Object
{
    private $request_type;
    private $route_pattern;
    private $callback;
    private $route_params;
    private $router;

    public function belongsTo($router)
    {
        $this->router = $router;
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
        if(count($route_params) > 0){
            foreach($route_params as $name => $val){
                unset($route_params[$name]);
                $name = str_replace(":","",$name);
                if($val[0] == '/'){
                    $val[0] = '';
                    $val = trim($val);
                }
                $route_params[$name] = $val;
            }
        }
        $this->route_params = $route_params;
    }

    public function getParams()
    {
        return $this->route_params;
    }

    public function via()
    {
        $request_types = func_get_args();
        $request_types_count = count($request_types);
        if($request_types_count > 0){
            for($k=0;$k<$request_types_count;$k++){
                $this->request_type = $request_types[$k];
                $this->router->addRoute($this);
            } 
        }

        return $this; # return the route instance to allow future extension
    }

    /**
     * Execute this route
     */
    public function execute($params = array())
    {
        $app = $this->router->getAppInstance();
        return $app->trigger("system.routing.route.execute", function($event){
          $app = $event->props('app');
          $route = $event->props('route');
          $params = $event->props('params');
          if(count($params) == 0){
            $params = $route->getParams();
          }
          //enhancement, detect the type of the callback
          $callback = $route->getCallback();
          if(is_string($callback)){
            $callback_comps = explode("#", $callback);
            $callback_object_class = $callback_comps[0]; //starting from root namespace
            $callback_object_class = str_replace(".", "\\", $callback_object_class); 
            $callback_object = new $callback_object_class($app);
            $callback_object_method = $callback_comps[1];
            $callback = array($callback_object, $callback_object_method);
          }
          return call_user_func_array($callback, $params);
        }, array('app' => $app, 'route' => $this, 'params' => $params));
    }
}
