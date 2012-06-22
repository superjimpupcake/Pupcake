<?php
namespace Pupcake\Plugin\Express;

use Pupcake;

class Request extends Pupcake\Object
{
    private $route;
    private $plugin;
    private $args;

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

    public function type()
    {
        return $_SERVER['REQUEST_METHOD'];
    }

    public function mode()
    {
        return $this->plugin->getAppInstance()->getRequestMode();
    }

    public function arg($index)
    {
        if(!isset($this->args)){
            $url = $this->url();
            $url_comps = explode("/", $url);
            array_shift($url_comps);
            $this->args = $url_comps;  
        }

        return $this->args[$index];
    }
}

