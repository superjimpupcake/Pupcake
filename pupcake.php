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
    $this->route_map = array(); //initialize the route map
  }

  public static function instance()
  {
    if(!isset(static::$instance)){
      static::$instance = new static();
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

  public function addRoute($request_type, $route_pattern, $callback)
  {
    if($route_pattern == "/*"){
      $route_pattern = "/:path";
    }

    if(!isset($this->route_map[$request_type])){
      $this->route_map[$request_type] = array();
    }

    $this->route_map[$request_type][$route_pattern] = $callback;
  }

  public function getRouteMap()
  {
    return $this->route_map;
  }

  /**
   * Match URI
   *
   * Parse this route's pattern, and then compare it to an HTTP resource URI
   * This method was modeled after the techniques demonstrated by Dan Sosedoff at:
   *
   * http://blog.sosedoff.com/2009/09/20/rails-like-php-url-router/
   *
   * @param   string  $uri A Request URI
   * @param   string  $route_pattern The route pattern
   * @return  bool
   */
  public function matches( $uri, $route_pattern ) 
  {
    $this->params = array(); //clear possible previous matched params
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

  public function getCallback(){
    return $this->callback;
  }

  public function via()
  {
    $router = Router::instance();
    $request_types = func_get_args();
    $request_types_count = count($request_types);
    if($request_types_count > 0){
      for($k=0;$k<$request_types_count;$k++){
        $router->addRoute($request_types[$k], $this->route_pattern, $this->callback);
      } 
    }
  }
}

class Pupcake
{
  private static $instance;
  private $request_type;
  private $query_path;
  private $router;
  private $return_output;
  private $request_mode; 

  public function __construct()
  {
    $this->request_mode = "external"; //default request mode is external
    $this->return_output = false;
    $this->router = Router::instance();
  }

  public static function instance()
  {
    if(!isset(static::$instance)){
      static::$instance = new static();
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
    $this->router->setRouteNotFoundHanlder($callback);
  }

  public function sendInternalRequest($request_type, $query_path)
  {
    $is_nested_internal_request = false;
    if($this->request_mode == 'internal'){ //this is a nested interal request
      $is_nested_internal_request = true;
    }

    $this->setRequestMode("internal");
    $current_request_type = $_SERVER['REQUEST_METHOD'];
    $_SERVER['REQUEST_METHOD'] = $request_type; 
    $this->setQueryPath($query_path);
    $this->setReturnOutput(true);
    $output = $this->run();
    $_SERVER['REQUEST_METHOD'] = $current_request_type;
    if(!$is_nested_internal_request){
      $this->setReturnOutput(false);
      $this->setRequestMode("external");
    }

    return $output;
  }

  public function forward($request_type, $query_path)
  {
    return $this->sendInternalRequest($request_type, $query_path);
  }

  public function run()
  {
    $route_map = $this->router->getRouteMap();
    $request_matched = false;
    if($this->request_mode == 'external'){
      $query_path = "/";
      $script_base_name = basename($_SERVER['SCRIPT_FILENAME']);
      if($_SERVER['PHP_SELF'] != '/'.$script_base_name){
        $query_path = str_replace($script_base_name."/", "", $_SERVER['PHP_SELF']);
      }
      $this->setQueryPath($query_path);
    }
    $output = "";
    if(count($route_map) > 0){
      $request_types = array($_SERVER['REQUEST_METHOD'], "*");
      foreach($request_types as $request_type){
        if(isset($route_map[$request_type]) && count($route_map[$request_type]) > 0){
          foreach($route_map[$request_type] as $route_pattern => $callback){
            //once we found there is a matching route, stop
            if($this->router->matches($this->query_path, $route_pattern)){
              $request_matched = true;
              $output = call_user_func_array($callback, $this->router->getMatchParams());
              break 2;
            }
          }
        }
      }
    }

    if(!$request_matched){
      //route not found
      header("HTTP/1.0 404 Not Found");
      $output = $this->router->processRouteNotFound();
    }

    if($this->return_output){
      return $output;
    }
    else{
      ob_start();
      print $output;
      $output = ob_get_contents();
      ob_end_clean();
      print $output;
    }
  }

  public function setQueryPath($query_path)
  {
    if(strlen($query_path) > 0 && $query_path[0] != '/'){
      $query_path = "/".$query_path;
    }
    $this->query_path = $query_path;
  }

  public function setReturnOutput($return_output)
  {
    $this->return_output = $return_output;
  }

  public function setRequestMode($request_mode)
  {
    $this->request_mode = $request_mode; 
  }

  public function redirect($uri)
  {
    if($this->request_mode == 'external'){
      header("Location: ".$uri);
    }
    else if($this->request_mode == 'internal'){
      return $this->forward('GET', $uri);
    }
  }

}
