<?php
/**
 * This is a plugin to simulate some functionalities in node.js
 */

namespace Pupcake\Plugin\Node;

class Main extends \Pupcake\Plugin
{

  private $event_loop;
  private $global_object_map; //the global objects map
  private $global_objects; //the global object instances
  private $module_map; //the modules map
  private $modules; //the module instances

  public function load($config = array())
  {

    // start the event loop
    $this->event_loop = uv_default_loop();

    //set up the global object map
    $this->global_object_map = array(
      'console' => 'Console',
      'process' => 'Process',
    );

    $this->global_objects = array();

    //set up the module map
    $this->module_map = array(
      'os' => 'OS', 
    );

    $this->modules = array();

    $app = $this->getAppInstance();
    $app->on("system.run", function(){
      uv_run();
    });

  }

  /**
   * import a module based on name, similar to node.js's require
   */
  public function import($name)
  {
    $object = null;
    if(!isset($this->modules[$name])){
      if(isset($this->module_map[$name])){
        $object_class = '\\Pupcake\\Plugin\\Node\\Module\\'.$this->module_map[$name];
        $object = new $object_class($this);
        $this->modules[$name] = $object;
      }
    }

    return $object;

  }

  /**
   * get a global object instance
   */
  public function globalObject($name)
  {
    $object = null;
    if(!isset($this->global_objects[$name])){
      if(isset($this->global_object_map[$name])){
        $object_class = '\\Pupcake\\Plugin\\Node\\GlobalObject\\'.$this->global_object_map[$name];
        $object = new $object_class($this);
        $this->global_object_map[$name] = $object;
      }
    }

    return $object;
  }

  /**
   * get current event loop
   */
  public function getEventLoop()
  {
    return $this->event_loop;
  }
}
