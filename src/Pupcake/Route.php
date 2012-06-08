<?php
namespace Pupcake;

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

    /**
     * Execute this route
     */
    public function execute($params = array())
    {
        if(count($params) == 0){
            $params = $this->getParams();
        }
        return call_user_func_array($this->getCallback(), $params);
    }
}
