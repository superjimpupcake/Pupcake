<?php
namespace Pupcake\Plugin\Express;

use Pupcake;

class Request extends Pupcake\Object
{
    private $route;
    private $app_instance;
    private $plugin;
    private $args;

    public function __construct($plugin, $route)
    {
        $this->app_instance = $plugin->getAppInstance();
        $this->plugin = $plugin;
        $this->route = $route;
        $plugin->trigger("pupcake.plugin.express.request.create", "", array("request" => $this));
    }

    public function getAppInstance()
    {
        return $this->app_instance;
    }

    public function setRoute($route)
    {
        $this->route = $route;
    }

    public function params($param_name)
    {
        $params = $this->route->getParams();
        $result = "";
        if (isset($params[$param_name])) {
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
        if (!isset($this->args)) {
            $url = $this->url();
            $url_comps = explode("/", $url);
            array_shift($url_comps);
            $this->args = $url_comps;  
        }

        return $this->args[$index];
    }

    public function body($key = "", $value = null)
    {
        $request_method = $_SERVER['REQUEST_METHOD'];
        if ($value === NULL) {
            if ($key == "") {
                return $GLOBALS["_$request_method"];
            }
            else{
                if (isset($GLOBALS["_$request_method"][$key])) {
                    return $GLOBALS["_$request_method"][$key];
                }
            }
        }
        else{
            $GLOBALS["_$request_method"][$key] = $value;
        }
    }
}
