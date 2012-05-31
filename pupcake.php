<?php

namespace Pupcake;

class Router
{
  private static $instance;
  private $route_map;
  private $params;
  private $route_not_found_handler;

  public function __construct()
  {
    $this->route_map = array(); //initialize the route map, only storing related routes for the current request type
  }

  public static function instance()
  {
    if(!isset(static::$instance)){
      static::$instance = new self();
    }
    return static::$instance; 
  }

  public function getMatchParams(){
    $result = array();
    if(count($this->params) > 0){
      $result = $this->params;
    }
    return $result;
  }

  public function addRoute($route_pattern, $callback)
  {
    if(!isset($this->route_map[$route_pattern])){ //make sure later added route pattern will not affect the previous added one
      if($route_pattern == "/*"){
        $route_pattern = "/:path";
      }
      $this->route_map[$route_pattern] = $callback;
    }
  }

  public function getRouteMap()
  {
    return $this->route_map;
  }

  /**
   * Matches URI?
   *
   * Parse this route's pattern, and then compare it to an HTTP resource URI
   * This method was modeled after the techniques demonstrated by Dan Sosedoff at:
   *
   * http://blog.sosedoff.com/2009/09/20/rails-like-php-url-router/
   *
   * @param   string  $uri A Request URI
   * @return  bool
   */
  public function matches( $uri, $route_pattern ) 
  {
    //Extract URL params
    preg_match_all('@:([\w]+)@', $route_pattern, $param_names, PREG_PATTERN_ORDER);
    $param_names = $param_names[0];

    //Convert URL params into regex patterns, construct a regex for this route
    $pattern_as_regex = preg_replace_callback('@:[\w]+@', array($this, 'convertPatternToRegex'), $route_pattern);
    if ( substr($route_pattern, -1) === '/' ) {
      $pattern_as_regex = $pattern_as_regex . '?';
    }
    $pattern_as_regex = '@^' . $pattern_as_regex . '$@';

    //Cache URL params' names and values if this route matches the current HTTP request
    if ( preg_match($pattern_as_regex, $uri, $param_values) ) {
      array_shift($param_values);
      foreach ( $param_names as $index => $value ) {
        $val = substr($value, 1);
        if ( isset($param_values[$val]) ) {
          $this->params[$val] = urldecode($param_values[$val]);
        }
      }
      return true;
    } else {
      return false;
    }
  }

  /**
   * Convert a URL parameter (ie. ":id") into a regular expression
   * @param   array   URL parameters
   * @return  string  Regular expression for URL parameter
   */
  private function convertPatternToRegex( $matches ) 
  {
    $key = str_replace(':', '', $matches[0]);
    return '(?P<' . $key . '>[a-zA-Z0-9_\-\.\!\~\*\\\'\(\)\:\@\&\=\$\+,%]+)';
  }

  private function defaultRouteNotFoundHandler()
  {
    return "Invalid Request!";
  }

  /**
   * set route not found handler
   */
  public function setRouteNotFoundHanlder($callback)
  {
    $this->route_not_found_handler = $callback;
  }

  /**
   * process route not found 
   */
  public function processRouteNotFound()
  {
    if(!isset($this->route_not_found_handler)){
      return $this->defaultRouteNotFoundHandler();
    }
    else{
      $callback = $this->route_not_found_handler;
      return $callback();
    }
  }

  public function redirect($uri)
  {
    header("Location: ".$uri);
  }

}

class Route
{

  private $route_pattern;
  private $callback;

  public function __construct($route_pattern, $callback)
  {
    if($route_pattern[0] != '/'){
      $route_pattern = "/".$route_pattern;
    }
    $this->route_pattern = $route_pattern;
    $this->callback = $callback;
  }

  public function getPattern()
  {
    return $this->route_pattern;
  }


  public function via()
  {
    $router = Router::instance();
    $request_types = func_get_args();
    $request_types_count = count($request_types);
    if($request_types_count > 0){
      for($k=0;$k<$request_types_count;$k++){
        if($request_types[$k] == $_SERVER['REQUEST_METHOD'] || $request_types[$k] == '*'){
          //add route to the map only when there is a request type matching
          $router->addRoute($this->route_pattern, $this->callback);
          break;
        }
      } 
    }
  }
}

class Pupcake
{
  private static $instance;

  public static function instance()
  {
    if(!isset(static::$instance)){
      static::$instance = new self();
    }
    return static::$instance; 
  }


  public function match($route_pattern, $callback)
  {
    $route = new Route($route_pattern, $callback);
    return $route;
  }

  public function get($route_pattern, $callback)
  {
    return $this->match($route_pattern, $callback)->via('GET');
  }

  public function post($route_pattern, $callback)
  {
    return $this->match($route_pattern, $callback)->via('POST');
  }

  public function delete($route_pattern, $callback)
  {
    return $this->match($route_pattern, $callback)->via('DELETE');
  }

  public function put($route_pattern, $callback)
  {
    return $this->match($route_pattern, $callback)->via('PUT');
  }

  public function options($route_pattern, $callback)
  {
    return $this->match($route_pattern, $callback)->via('OPTIONS');
  }

  public function any($route_pattern, $callback)
  {
    return $this->match($route_pattern, $callback)->via('*');
  } 

  public function notFound($callback)
  {
    $router = Router::instance();
    $router->setRouteNotFoundHanlder($callback);
  }

  public function run()
  {

    ob_start();

    $router = Router::instance();
    $route_map = $router->getRouteMap();
    $request_matched = false;
    $query_path = "/";
    if($_SERVER['PHP_SELF'] != '/index.php'){
      $query_path = str_replace("index.php/", "", $_SERVER['PHP_SELF']);
      if(strlen($query_path) > 0 && $query_path[0] != '/'){
        $query_path = "/".$query_path;
      }
    }
    $output = "";
    if(count($route_map) > 0){
      foreach($route_map as $route_pattern => $callback){
        //once we found there is a matching route, stop
        if($router->matches($query_path, $route_pattern)){
          $request_matched = true;
          $output = call_user_func_array($callback, $router->getMatchParams());
          break;
        }
      }
    }

    if(!$request_matched){
      //route not found
      header("HTTP/1.0 404 Not Found");
      $output = $router->processRouteNotFound();
    }

    print $output;  
    $output = ob_get_contents();
    ob_end_clean();

    print $output;
  }
}
