<?php
/**
 * This is a plugin to simulate some functionalities in node.js
 */

namespace Pupcake\Plugin\Node;

class Main extends \Pupcake\Plugin
{

  private $event_loop;
  private $global_objects;

  public function load($config = array())
  {

    // start the event loop
    $this->event_loop = uv_default_loop();
    $this->global_objects = array(
      'console' => new GlobalObject\Console($this),
      'process' => new GlobalObject\Process($this),
    );

    $app = $this->getAppInstance();
    $app->on("system.run", function(){
      uv_run();
    });

  }

  /**
   * import a package, similar to node.js's require
   */
  public function import($package_name)
  {
  
  }

  /**
   * get a global object
   */
  public function globalObject($object_name)
  {
    $object = null;
    if(isset($this->global_objects[$object_name])){
      $object = $this->global_objects[$object_name]; 
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
