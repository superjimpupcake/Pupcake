<?php
namespace Pupcake\Plugin\Express;

use Pupcake;

class Response extends Pupcake\Object
{
    private $plugin;
    private $route;
    private $req;

    public function __construct($plugin, $route, $req)
    {
        $this->plugin = $plugin;
        $this->route = $route;
        $this->req = $req;
        $plugin->trigger("pupcake.plugin.express.response.create", "", array("response" => $this));
    }

    public function send($output)
    {
        $this->route->storageSet('output', $output); 
    }

    public function redirect($uri)
    {
        $this->plugin->getAppInstance()->redirect($uri);
    }

    public function forward($request_type, $uri)
    {
        $this->plugin->getAppInstance()->forward($request_type, $uri);
        $route = $this->plugin->getAppInstance()->getRouter()->getMatchedRoute();
        return $route->storageGet('output');
    }

    public function toRoute($request_type, $route_pattern, $params)
    {
        $router = $this->plugin->getAppInstance()->getRouter();
        $route = $router->getRoute($request_type, $route_pattern);
        $route->setParams($params);
        $this->req->setRoute($route);
        $route->execute(array($this->req, $this));
        $output = $this->route->storageGet('output');
        return $output;
    } 
}

