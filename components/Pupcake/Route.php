<?php

namespace Pupcake;

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

