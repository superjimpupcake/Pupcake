<?php
/**
 * Express Service
 */
namespace Pupcake\Service;

use Pupcake;

class Express extends Pupcake\Service
{
    public function start($app)
    {
        $this->on("system.request.found", function($event, $handler){
            $route = $event->props('route');
            $req = new Express\Request($route);
            $res = new Express\Response($app, $route);
            $route->execute(array($req, $res)); //execuite route and override params
            return $route->storageGet('output');
        });
    }
}
