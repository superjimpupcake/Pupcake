<?php
namespace Pupcake\Plugin\Express;

use Pupcake;

class Response extends Pupcake\Object
{
    private $plugin;
    private $route;

    public function __construct($plugin, $route)
    {
        $this->plugin = $plugin;
        $this->route = $route;
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
}

