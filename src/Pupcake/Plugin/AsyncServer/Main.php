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

  public function load($config = array())
  {
    $app = $this->getAppInstance();

    $app->method("listen", array($this, "listen")); //add listen method
    $app->method("setHeader", array($this, "setHeader")); //reopen setHeader method

    $plugin = $this;

    $app->handle("system.run", function($event) use ($plugin){
      uv_listen($plugin->getTCP(),200, function($server) use ($event, $plugin){
        $client = uv_tcp_init();
        uv_accept($server, $client);
        uv_read_start($client, function($client, $nread, $buffer) use ($event, $plugin){
          $result = $plugin->httpParseExecute($buffer);
          if(is_array($result)){
            $_SERVER['REQUEST_METHOD'] = $result['REQUEST_METHOD'];
            $_SERVER['PATH_INFO'] = $result['path'];
            $_SERVER['HTTP_HOST'] = $result['headers']['Host'];
            $_SERVER['HTTP_USER_AGENT'] = $result['headers']['User-Agent'];

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
}
