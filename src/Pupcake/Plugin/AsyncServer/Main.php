<?php
/**
 * A plugin to turn pupcake into an async server
 */

namespace Pupcake\Plugin\AsyncServer;

use Pupcake;

class Main extends Pupcake\Plugin
{
  private $tcp;

  public function load($config = array())
  {
    $app = $this->getAppInstance();

    $app->method("listen", array($this, "listen"));

    $plugin = $this;

    $app->handle("system.run", function($event) use ($plugin){
      uv_listen($plugin->getTCP(),200, function($server) use ($event){
        $client = uv_tcp_init();
        uv_accept($server, $client);
        uv_read_start($client, function($client, $nread, $buffer) use ($event){
          $parser = http_parser_init();
          $result = array();
          if (http_parser_execute($parser, $buffer, $result)){
            $_SERVER['REQUEST_METHOD'] = $result['REQUEST_METHOD'];
            $_SERVER['PATH_INFO'] = $result['path'];

            $app = $event->props('app');
            $output = $app->sendRequest("external", $_SERVER['REQUEST_METHOD'], $_SERVER['PATH_INFO'], $app->getRouter()->getRouteMap());

            $buffer = "HTTP/1.1 200 OK\n\n".$output;
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

  public function listen($ip, $port = 8080)
  {
    $this->tcp = uv_tcp_init();
    uv_tcp_bind($this->tcp, uv_ip4_addr($ip, $port));
  }

  public function getTCP()
  {
    return $this->tcp;
  }
}
