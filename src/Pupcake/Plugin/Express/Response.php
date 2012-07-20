<?php
namespace Pupcake\Plugin\Express;

use Pupcake;

class Response extends Pupcake\Object
{
  private $plugin;
  private $app_instance;
  private $route;
  private $req;
  private $in_inner_route;

  public function __construct($plugin, $route, $req)
  {
    $this->plugin = $plugin;
    $this->app_instance = $plugin->getAppInstance();
    $this->route = $route;
    $this->req = $req;
    $this->in_inner_route = false;
    $plugin->trigger("pupcake.plugin.express.response.create", "", array("response" => $this));
  }

  public function getAppInstance()
  {
    return $this->app_instance;
  }

  public function send($output)
  {
    if($this->in_inner_route){
      $this->route->storageSet('inner_route_output', $output); 
    }
    else{
      $this->route->storageSet('output', $output); 
    }
  }

  public function contentType($content_type)
  {
    header("Content-type: $content_type");
    return $this;
  }

  public function redirect($uri)
  {
    $this->plugin->getAppInstance()->redirect($uri);
  }

  public function forward($request_type, $uri, $request_params = array())
  {
    $request_type = strtoupper($request_type);
    $tmp = $GLOBALS["_$request_type"]; //store current request variables in tmp
    $GLOBALS["_$request_type"] = $request_params;
    $this->plugin->getAppInstance()->forward($request_type, $uri);
    $route = $this->plugin->getAppInstance()->getRouter()->getMatchedRoute();
    $GLOBALS["_$request_type"] = $tmp; //restore current request variables
    return $route->storageGet('output');
  }

  public function toRoute($request_type, $route_pattern, $params)
  {
    $this->in_inner_route = true;
    $router = $this->plugin->getAppInstance()->getRouter();
    $route = $router->getRoute($request_type, $route_pattern);
    $route->setParams($params);
    $this->req->setRoute($route);
    $route->execute(array($this->req, $this));
    $this->req->setRoute($this->route); //set back the request route
    $this->in_inner_route = false;
    return $this->route->storageGet('inner_route_output');
  }

  public function inInnerRoute()
  {
    return $this->in_inner_route;
  }
}

