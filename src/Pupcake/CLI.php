<?php
/**
 * Pupcake Command Line Interface
 */
namespace Pupcake;

class CLI extends Object
{
  private $event_queue;
  private $plugins; //all plugins in the application
  private $plugins_loaded;
  private $events_helpers;

  public function __construct($configuration = array())
  {
    set_error_handler(array($this, 'handleError'), E_ALL);
    register_shutdown_function(array($this, 'handleShutdown'));

    //define methods that can be reopened
    $this->method("setHeader", array($this, "setHeaderNative"));
    $this->method("redirect", array($this, "redirectNative"));

    $this->event_queue = array();
    $this->events_helpers = array();
    $this->plugin_loading = false;
    $this->plugins_loaded = false;
  }

  /**
   * add a callback to the event, one event, one handler callback
   * the handler callback is swappable, so later handler callback can override previous handler callback
   */
  public function on($event_name, $handler_callback)
  {
    $event = null;
    if(!isset($this->event_queue[$event_name])){
      $event = new Event($event_name);
      $this->event_queue[$event_name] = $event;
    }

    $event = $this->event_queue[$event_name];
    $event->setHandlerCallback($handler_callback);
  }

  /**
   * add alias to the on method, handle
   */
  public function handle($event_name, $handler_callback)
  {
    $this->on($event_name, $handler_callback);
  }

  /**
   * trigger an event with a default handler callback
   */
  public function trigger($event_name, $default_handler_callback = "", $event_properties = array())
  {
    $event = null;
    if(isset($this->event_queue[$event_name])){ //if the event already exists, use the handler callback for the event instead of the default handler callback
      $event = $this->event_queue[$event_name];
      $event->setProperties($event_properties);

      $handler_callback = $event->getHandlerCallback();
      if(is_callable($handler_callback)){ 
        $result = call_user_func_array($handler_callback, array($event));
        $event->setHandlerCallbackReturnValue($result);
      }
    }
    else{ //event does not exist yet, use the default handler callback
      $event = new Event($event_name);
      $event->setProperties($event_properties);
      if(is_callable($default_handler_callback)){
        $event->setHandlerCallback($default_handler_callback);
        $result = call_user_func_array($default_handler_callback, array($event));
        $event->setHandlerCallbackReturnValue($result);
        $this->event_queue[$event_name] = $event;
      }
    }

    $result = $event->getHandlerCallbackReturnValue();

    return $result;
  }

  /**
   * tell the sytem to use a plugin
   */
  public function usePlugin($plugin_name, $config = array())
  {
    if(!isset($this->plugins[$plugin_name])){
      $plugin_name = str_replace(".", "\\", $plugin_name);
      //allow plugin name to use . sign
      $plugin_class_name = $plugin_name."\Main";
      $this->plugins[$plugin_name] = new $plugin_class_name();
      $this->plugins[$plugin_name]->setAppInstance($this);
      $this->plugins[$plugin_name]->load($config);
    }
    return $this->plugins[$plugin_name];
  }

  /**
   * load all plugins
   */
  public function loadAllPlugins()
  {
    if(count($this->plugins) > 0){
      foreach($this->plugins as $plugin_name => $plugin){
        $event_helpers = $plugin->getEventHelperCallbacks();
        if(count($event_helpers) > 0){
          foreach($event_helpers as $event_name => $callback){
            if(!isset($this->events_helpers[$event_name])){
              $this->events_helpers[$event_name] = array();
            }
            $this->events_helpers[$event_name][] = $plugin; //add the plugin object to the map
          }
        }
      }

      //register all event helpers
      if(count($this->events_helpers) > 0){
        foreach($this->events_helpers as $event_name => $plugin_objs){
          if(count($plugin_objs) > 0){
            $this->plugin_loading = true;
            $this->on($event_name, function($event) use ($plugin_objs) {
              return call_user_func_array(array($event, "register"), $plugin_objs)->start();
            });
            $this->plugin_loading = false;
          }
        }
      }
    }
  }

  public function handleError($severity, $message, $file_path, $line)
  {
    $error = new Error($severity, $message, $file_path, $line);
    $this->trigger('system.error.detected', '', array('error' => $error));
  }

  public function handleShutdown()
  {
    $this->trigger('system.shutdown');
  }

}
