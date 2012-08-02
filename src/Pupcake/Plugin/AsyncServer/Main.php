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
  private $request_count;

  public function load($config = array())
  {
    $app = $this->getAppInstance();

    $app->method("listen", array($this, "listen")); //add listen method
    $app->method("setHeader", array($this, "setHeader")); //reopen setHeader method

    $this->request_count = 0;

    $plugin = $this;

    $app->handle("system.run", function($event) use ($plugin){
      uv_listen($plugin->getTCP(),200, function($server) use ($event, $plugin){
        $plugin->incrementRequestCount();
        $client = uv_tcp_init();
        uv_accept($server, $client);
        uv_read_start($client, function($client, $nread, $buffer) use ($event, $plugin){
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

            $app = $event->props('app');
            $output = $app->sendRequest("external", $_SERVER['REQUEST_METHOD'], $_SERVER['PATH_INFO'], $app->getRouter()->getRouteMap());
            $header = $plugin->getHeader();

            if(strlen($header) > 0){
              $header = $header."\r\n";
            }
            else{
              $header = "";
            }

            $buffer = "HTTP/1.1 200 OK\r\n$header\r\n$output";
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

  public function setHeader($header)
  {
    $this->header = $header;
  }

  public function getHeader()
  {
    return $this->header;
  }

  public function incrementRequestCount()
  {
    $this->request_count ++;
  }

  public function getRequestCount()
  {
    return $this->request_count;
  }
}
