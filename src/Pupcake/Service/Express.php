<?php
/**
 * Express Service
 */
namespace Pupcake\Service;

use Pupcake;

class Express extends Pupcake\Service
{
    public function start($config = array())
    {
        $service = $this;
        $this->on("system.request.found", function($event) use ($service) {
            $route = $event->props('route');
            $req = new Express\Request($route);
            $res = new Express\Response($service, $route);
            $route->execute(array($req, $res)); //execuite route and override params
            return $route->storageGet('output');
        });
    }
}
