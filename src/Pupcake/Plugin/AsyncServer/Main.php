<?php
/**
 * A plugin to turn pupcake into an async server
 */

namespace Pupcake\Plugin\AsyncServer;

use Pupcake;

class Main extends Pupcake\Plugin
{
  private $tcp;
  private $header;
  private $protocol;
  private $status_code;
  private $status_message;
  private $router;

  public function load($config = array())
  {
    $app = $this->getAppInstance();

    $this->app = $app;

    $app->method("listen", array($this, "listen")); //add listen method
    $app->method("setHeader", array($this, "setHeader")); //reopen setHeader method
    $app->method("redirect", array($this, "redirect")); //reopen redirect method

    $this->router = $app->getRouter();
    $this->router->method("processRouteMatching", array($this, "processRouteMatching")); //reopen processRouteMatching

    $this->protocol = "HTTP/1.1"; // default protocol
    $this->status_code = 200; //default status code
    $this->status_message = "OK"; //default status message

    $plugin = $this;

    $app->handle("system.run", function($event) use ($plugin){
      $app = $event->props('app');
      $route_map = $app->getRouter()->getRouteMap(); //load route map only once 
      uv_listen($plugin->getTCP(),100, function($server) use ($event, $plugin, $app, $route_map){
        $client = uv_tcp_init();
        uv_accept($server, $client);
        uv_read_start($client, function($client, $nread, $buffer) use ($event, $plugin, $app, $route_map){
          $client_info = uv_tcp_getpeername($client);
          if(is_array($client_info)){
            $_SERVER['REMOTE_ADDR'] = $client_info['address'];
          }
          $result = $plugin->httpParseExecute($buffer);
          if(is_array($result)){
            $request_method = $result['REQUEST_METHOD'];

            //constructing server variables
            $_SERVER['REQUEST_METHOD'] = $result['REQUEST_METHOD'];
            $_SERVER['PATH_INFO'] = $result['path'];
            $_SERVER['HTTP_HOST'] = $result['headers']['Host'];
            $_SERVER['HTTP_USER_AGENT'] = $result['headers']['User-Agent'];

            //constructing global variables
            if($request_method == 'GET'){
              $result['headers']['body'] = $result['query']; //bind body to query if it is a get request
            }

            $GLOBALS["_$request_method"] = explode("&", $result['headers']['body']); 

            $output = $app->sendRequest("external", $_SERVER['REQUEST_METHOD'], $_SERVER['PATH_INFO'], $route_map);
            $header = $plugin->getHeader();

            if(strlen($header) > 0){
              $header = $header."\r\n";
            }
            else{
              $header = "";
            }

            $protocol = $plugin->getProtocol();
            $status_code = $plugin->getStatusCode();
            $status_message = $plugin->getStatusMessage();

            $buffer = "$protocol $status_code $status_message\r\n$header\r\n$output";
            uv_write($client, $buffer, function($client, $stat){
              uv_close($client,function(){
                //    echo "connection closed\n";
              });
            });
          }
        });
      });

      uv_run();
    });
  }

  public function httpParseExecute($buffer)
  {
    $result = array();
    $parser = http_parser_init();
    http_parser_execute($parser, $buffer, $result);
    return $result;
  }

  public function listen($ip, $port = 8080)
  {
    $this->tcp = uv_tcp_init();
    uv_tcp_bind($this->tcp, uv_ip4_addr($ip, $port));
  }

  public function getTCP()
  {
    return $this->tcp;
  }

  public function setProtocol($protocol)
  {
    $this->protocol = $protocol;
  }

  public function getProtocol()
  {
    return $this->protocol;
  }

  public function setStatusCode($status_code)
  {
    $this->status_code = $status_code;
  }

  public function getStatusCode()
  {
    return $this->status_code;
  }

  public function setStatusMessage($status_message)
  {
    $this->status_message = $status_message;
  }

  public function getStatusMessage()
  {
    return $this->status_message;
  }

  public function setHeader($header)
  {
    $this->header = $header;
  }

  public function getHeader()
  {
    return $this->header;
  }

  public function redirect($uri)
  {
    $app = $this->app;
    $request_mode = $app->getRequestMode();
    if($request_mode == 'external'){
      $this->setStatusCode(302);
      $this->setHeader("Location: $uri");
    }
    else if($request_mode == 'internal'){
      return $app->forward('GET', $uri);
    }
  }

  public function processRouteMatching($event)
  {
    $request_type = $event->props('request_type');
    $uri = $event->props('query_path');
    $route_pattern= $event->props('route_pattern');
    $result = false;
    $params = array();

    $route_pattern_length = strlen($route_pattern);
    $path_pos = strpos($route_pattern, "*path"); //see if there is *path exists
    if($path_pos !== FALSE){
      $first_part_of_path = substr($route_pattern, 0, $path_pos);
      if(substr($uri, 0, $path_pos) == $first_part_of_path){
        $uri_length = strlen($uri);
        $params[":path"] = substr($uri, $path_pos, $uri_length - $path_pos);
        $route = $this->router->getRoute($request_type, $route_pattern);
        $route->setParams($params);
        $this->router->setMatchedRoute($route); 
        $result = true;
        return $result;
      }
    }

    $uri_comps = explode("/", $uri);
    $uri_comps_count = count($uri_comps);
    $route_pattern_comps = explode("/", $route_pattern);
    $route_pattern_comps_count = count($route_pattern_comps);
    if($uri_comps_count == $route_pattern_comps_count){
      for($k=1;$k<$route_pattern_comps_count;$k++){ //we should start from index 1 since index 0 is the /
        if($route_pattern_comps[$k][0] == ":"){
          $token = $route_pattern_comps[$k];
          $params[$token] = $uri_comps[$k];
          $route_pattern_comps[$k] = "";
          $uri_comps[$k] = "";
        }
      }

      $uri_reformed = implode("/",$uri_comps);
      $route_pattern_reformed = implode("/",$route_pattern_comps);
      $route = $this->router->getRoute($request_type, $route_pattern);
      $route->setParams($params);

      if($uri_reformed == $route_pattern_reformed){
        $results = $this->app->trigger("system.routing.route.matched", "", array('route' => $route));

        //the result can be either a boolean or an array 
        $result = true;
        if( is_array($results) && count($results) > 0 ){  //the result is an array
          foreach($results as $matched){
            if(!$matched){
              $result = false;
              break;
            }
          }
        }
        else if($results === FALSE){
          $result = false; 
        }

        if($result){ 
          $this->router->setMatchedRoute($route); 
        }

      }
    }

    return $result;
  }
}
