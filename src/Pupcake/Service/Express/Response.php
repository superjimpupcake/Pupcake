<?php
namespace Pupcake\Service\Express;

use Pupcake;

class Response extends Pupcake\Object
{
    private $service;
    private $route;

    public function __construct($service, $route)
    {
        $this->service = $service;
        $this->route = $route;
    }

    public function send($output)
    {
        $this->route->storageSet('output', $output); 
    }

    public function redirect($uri)
    {
        $this->service->getContext()->redirect($uri);
    }

    public function forward($request_type, $uri)
    {
        $this->service->getContext()->forward($request_type, $uri);
        $route = $this->service->getContext()->getMatchedRoute();
        return $route->storageGet('output');
    }
}

