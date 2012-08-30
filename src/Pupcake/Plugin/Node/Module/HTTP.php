<?php
namespace Pupcake\Plugin\Node\Module;

class HTTP extends \Pupcake\Plugin\Node\Module
{
  private $http_parser;
  private $server_request;
  private $server_response;

  public function createServer($request_listener)
  {
    $this->server_request = new HTTP\ServerRequest();
    $this->server_response = new HTTP\ServerResponse();
    call_user_func_array($request_listener, array($this->server_request, $this->server_response));
    return $this;    
  } 

  public function listen($port, $host = '127.0.0.1')
  {
    $self = $this;

    $node = $this->getNode();
    $process = $node->import("process");

    $process->nextTick(function() use ($port, $host, $self){ //make this async!
      $tcp = uv_tcp_init();

      uv_tcp_bind($tcp, uv_ip4_addr($host, $port));

      uv_listen($tcp,100, function($server) use ($self) {
        $client = uv_tcp_init();
        uv_accept($server, $client);
        
        uv_read_start($client, function($socket, $nread, $buffer) use ($self){

          $response = $self->getServerResponse();
          $status_code = $response->getStatusCode();
          $status_message = $response->getReasonPhrase();
          $headers = $response->getHeaders();
          $header = "";
          if(count($headers) > 0){
            foreach($headers as $key => $val){
              $header .= $key.": ".$val."\r\n";
            }
          } 
          $output = $response->getData();
          $buffer = "HTTP/1.1 $status_code $status_message\r\n$header\r\n$output";
           
          uv_write($socket, $buffer);
          uv_close($socket);
        });
      });

      uv_run(uv_default_loop()); //super tricky! need to run on another loop
    });
  }

  public function getServerRequest()
  {
    return $this->server_request; 
  }

  public function getServerResponse()
  {
    return $this->server_response; 
  }

  public function getProtocol()
  {
    return $this->protocol; 
  }
}
