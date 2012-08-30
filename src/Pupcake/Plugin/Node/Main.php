<?php
/**
 * This is a plugin to simulate some functionalities in node.js
 */

namespace Pupcake\Plugin\Node;

class Main extends \Pupcake\Plugin
{

  private $event_loop;
  private $module_map; //the modules map
  private $modules; //the module instances

  public function load($config = array())
  {

    // start the event loop
    $this->event_loop = uv_default_loop();

    //set up the module map
    $this->module_map = array(
      'console' => 'Console',
      'process' => 'Process',
      'os' => 'OS', 
      'http' => 'HTTP',
    );

    $this->modules = array();

    $app = $this->getAppInstance();
    $app->on("system.shutdown", function(){
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
    else{
      $object = $this->modules[$name]; 
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
