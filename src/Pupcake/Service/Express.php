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
        $app->on("system.request.found", function($route) use ($app) {
            $req = new Pupcake\Object();
            $res = new Pupcake\Object();
            $req->method('params', function($param_name) use ($route){
                $params = $route->getParams();
                $result = "";
                if(isset($params[$param_name])){
                    $result = $params[$param_name];
                }
                return $result;
            });

            $res->method('send', function($output) use ($route){
               $route->storageSet('output', $output); 
            });

            $res->method('redirect', function($uri) use($app) {
                $app->redirect($uri);
            });

            $res->method('forward', function($request_type, $uri) use($app, $req, $res) {
                $app->forward($request_type, $uri);
                $route = $app->getRouter()->getMatchedRoute();
                return $route->storageGet('output');
            });

            call_user_func_array($route->getCallback(), array($req, $res));
            return $route->storageGet('output');
        });
    }
}
