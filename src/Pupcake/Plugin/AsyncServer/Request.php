<?php
namespace Pupcake\Plugin\AsyncServer;

class Request extends \Pupcake\Object
{
  private $app; //the application instance

  public function __construct($app)
  {
    $this->app = $app;
  } 

  public function ip()
  {
    return $_SERVER['REMOTE_ADDR'];
  }

  public function url()
  {
    return  $_SERVER['PATH_INFO'];
  }

  public function type()
  {
    return $_SERVER['REQUEST_METHOD'];
  }

  public function arg($index)
  {
    if(!isset($this->args)){
      $url = $this->url();
      $url_comps = explode("/", $url);
      array_shift($url_comps);
      $this->args = $url_comps;  
    }

    return $this->args[$index];
  }

  public function body($key = "", $value = null)
  {
    $request_method = $_SERVER['REQUEST_METHOD'];
    if($value === NULL){
      if($key == ""){
        return $GLOBALS["_$request_method"];
      }
      else{
        if(isset($GLOBALS["_$request_method"][$key])){
          return $GLOBALS["_$request_method"][$key];
        }
      }
    }
    else{
      $GLOBALS["_$request_method"][$key] = $value;
    }
  }

}
