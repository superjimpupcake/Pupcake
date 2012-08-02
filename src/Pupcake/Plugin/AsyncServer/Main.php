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

    $app->handle("system.request.output.return", function($event){
      uv_run();
    });
  }

  public function listen($ip, $port = 8080)
  {
    $this->tcp = uv_tcp_init();
    uv_tcp_bind($this->tcp, uv_ip4_addr($ip, $port));
    uv_listen($this->tcp,200, function($server){
      $client = uv_tcp_init();
      uv_accept($server, $client);
      uv_read_start($client, function($client, $nread, $buffer){
        $parser = http_parser_init();
        $result = array();
        if (http_parser_execute($parser, $buffer, $result)){
          $request_method = $result['REQUEST_METHOD'];
          $request_type = $result['path'];

          $output = print_r($result,true);

          $buffer = "HTTP/1.1 200 OK\n\n".$output;
          uv_write($client, $buffer, function($c, $client) use ($client){
            uv_close($client,function(){
              //    echo "connection closed\n";
            });
          });
        }
      });
    });

//    uv_run();
  }
}
