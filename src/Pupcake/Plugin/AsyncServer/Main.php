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

  public function load($config = array())
  {
    $app = $this->getAppInstance();

    $this->app = $app;

    $app->method("listen", array($this, "listen")); //add listen method
    $app->method("setHeader", array($this, "setHeader")); //reopen setHeader method
    $app->method("redirect", array($this, "redirect")); //reopen redirect method

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
            uv_write($client, $buffer, function($c, $client) use ($client){
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
}
