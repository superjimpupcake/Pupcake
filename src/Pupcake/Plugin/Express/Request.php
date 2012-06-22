<?php
namespace Pupcake\Plugin\Express;

use Pupcake;

class Request extends Pupcake\Object
{
    private $route;
    private $plugin;

    public function __construct($plugin, $route)
    {
        $this->plugin = $plugin;
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

    public function url()
    {
        return $this->plugin->getAppInstance()->getQueryPath();
    }
}

