<?php
/**
 * A plugin to turn pupcake into an async server
 */

namespace Pupcake\Plugin\AsyncServer;

use Pupcake;

class Main extends Pupcake\Plugin
{
  private $server; //the socket stream server
  private $secure_credentials; //the secure credential
  private $http_host; //the http_host
  private $server_port;
  private $header;
  private $protocol;
  private $status_code;
  private $status_message;
  private $router;

  public function load($config = array())
  {
    $app = $this->getAppInstance();

    $this->app = $app;
    $this->secure_credentials = null; //default we do not have secure credentials

    $app->method("listen", array($this, "listen")); //add listen method
    $app->method("setHeader", array($this, "setHeader")); //reopen setHeader method
    $app->method("redirect", array($this, "redirect")); //reopen redirect method
    $app->method("getProcessManager", array($this, "getProcessManager")); //expose getProcessManager
    $app->method("getTimer", function(){
      return new Timer();
    });
    $app->method("setSecure", array($this, "setSecure")); //set up ssl 

    $this->protocol = "HTTP/1.1"; // default protocol
    $this->status_code = 200; //default status code
    $this->status_message = "OK"; //default status message

    $plugin = $this;

    $app->handle("system.run", function($event) use ($plugin){
      $ip = $plugin->getHTTPHost();
      $port = $plugin->getSeverPort();
      $protocol = "tcp"; //the default protocol is tcp
      $secure_credentials = $plugin->getSecure();
      $server = null;
      if(is_array($secure_credentials)){
        $protocol = "ssl";
        $context = stream_context_create();
        //merge certificate file with private key file
        $cert_content = file_get_contents($secure_credentials['cert']);
        $key_content = file_get_contents($secure_credentials['key']);
        $cert_content = $cert_content.$key_content;
        $certificate = dirname($secure_credentials['cert'])."/certificate_concat.pem";
        file_put_contents($certificate, $cert_content);
        stream_context_set_option($context, 'ssl', 'local_cert', $certificate);
        stream_context_set_option($context, 'ssl', 'verify_peer', false);
        $server = stream_socket_server("$protocol://$ip:$port", $errno, $errstr, STREAM_SERVER_BIND|STREAM_SERVER_LISTEN, $context);
      }
      else{
        $server = stream_socket_server("$protocol://$ip:$port", $errno, $errstr);
      }
      $plugin->setServer($server);
      
      $app = $event->props('app');
      $route_map = $app->getRouter()->getRouteMap(); //load route map only once 
      $request = new Request($app);

      $loop = uv_default_loop();
      $poll = uv_poll_init_socket($loop, $server);

      uv_poll_start($poll, \UV::READABLE, function($poll, $stat, $ev, $server) use ($loop, $event, $plugin, $app, $route_map, $request){

        $client = stream_socket_accept($server);
        $client_ip_info = stream_socket_get_name($client,true);
        if($client_ip_info){
          $client_ip_info_comps = explode(":", $client_ip_info);
          $_SERVER['REMOTE_ADDR'] = $client_ip_info_comps[0];
        }

        uv_fs_read($loop, $client, function($client, $nread, $buffer) use ($event, $plugin, $app, $route_map, $request){
          $result = $plugin->httpParseExecute($buffer);
          if(is_array($result)){
            $request_method = $result['REQUEST_METHOD'];

            //constructing server variables
            $_SERVER['REQUEST_METHOD'] = $result['REQUEST_METHOD'];
            $_SERVER['PATH_INFO'] = $result['path'];
            $_SERVER['HTTP_HOST'] = $plugin->getHTTPHost();
            $_SERVER['HTTP_USER_AGENT'] = $result['headers']['User-Agent'];

            //constructing global variables
            if($request_method == 'GET'){
              $result['headers']['body'] = $result['query']; //bind body to query if it is a get request
            }

            $GLOBALS["_$request_method"] = explode("&", $result['headers']['body']); 

            $output = $app->trigger("system.server.response.body", function($event) use ($route_map, $app){
              return $app->sendRequest("external", $_SERVER['REQUEST_METHOD'], $_SERVER['PATH_INFO'], $route_map);
            }, array('request' => $request));

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
            fwrite($client, $buffer);
            fclose($client);
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
    $this->http_host = $ip;
    $this->server_port = $port;
  }

  public function setSecure($secure_credentials){
    $this->secure_credentials = $secure_credentials;
  }

  public function getSecure()
  {
    return $this->secure_credentials;
  }

  public function setServer($server)
  {
    $this->server = $server;
  }

  public function getServer()
  {
    return $this->server;
  }

  public function getSeverPort()
  {
    return $this->server_port;
  }

  public function getHTTPHost()
  {
    return $this->http_host;
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

  public function getCurrentDirectory()
  {
    return getcwd();
  }

  public function getProcessManager()
  {
    if(!isset($this->process_manager)){
      $this->process_manager = new ProcessManager($this);
    }
    return $this->process_manager;
  }
}
