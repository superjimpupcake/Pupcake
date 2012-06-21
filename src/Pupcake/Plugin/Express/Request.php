<?php
namespace Pupcake\Plugin\Express;

use Pupcake;

class Request extends Pupcake\Object
{
    private $route;

    public function __construct($route)
    {
        $this->route = $route;
    }

    public function setRoute($route)
    {
        $this->route = $route;
    }

    public function params($param_name)
    {
        $params = $this->route->getParams();
        $result = "";
        if(isset($params[$param_name])){
            $result = $params[$param_name];
        }
        return $result;

    }
}

