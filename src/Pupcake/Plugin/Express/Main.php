<?php
/**
 * Express plugin
 */
namespace Pupcake\Plugin\Express;

use Pupcake;

class Main extends Pupcake\Plugin
{

  private $route_map;

  public function setRouteMapToLookup($route_map)
  {
    $this->route_map = $route_map;
  }

  public function getRouteMapToLookup()
  {
    return $this->route_map;
  }

  public function getNextRouteFinder($route, $req, $res)
  {
    $plugin = $this;
    $next = function() use ($route, $req, $res, $plugin) { //find the next matching route

      $route_map = array();

      if($plugin->getRouteMapToLookup() === NULL){
        $route_map = $plugin->getAppInstance()->getRouter()->getRouteMap();
      }
      else{
        $route_map = $plugin->getRouteMapToLookup();
      }

      $current_route = $route;
      $current_route_request_type = $current_route->getRequestType();
      $current_route_pattern = $current_route->getPattern();

      //unset the current route 
      unset($route_map[$current_route_request_type][$current_route_pattern]);
      $plugin->setRouteMapToLookup($route_map);

      $output = ""; //return empty response by default

      $request_matched = $plugin->getAppInstance()->getRouter()->findMatchedRoute($_SERVER['REQUEST_METHOD'], $_SERVER['PATH_INFO'], $route_map);

      if($request_matched){ //we found the route
        $matched_route = $plugin->getAppInstance()->getRouter()->getMatchedRoute();
        $req->setRoute($matched_route);
        $next = $plugin->getNextRouteFinder($matched_route, $req, $res);
        if(is_callable($next)){
          $output = $matched_route->execute(array($req, $res, $next)); //execute route and override params
        }
      }

      return $output;

    };

    return $next;
  }

  public function load($config = array())
  {
    $this->route_map = null; //initially, set route map to null

    $plugin = $this;
    $this->on("system.request.found", function($event) use ($plugin) {
      $route = $event->props('route');
      $req = new Request($plugin, $route);
      $res = new Response($plugin, $route, $req);
      $next = $plugin->getNextRouteFinder($route, $req, $res);
      if(is_callable($next)){
        $route->execute(array($req, $res, $next)); //execute route and override params
      }
      return $route->storageGet('output');
    });
  }
}
