<?php

/**
 * The Pupcake service class
 */
namespace Pupcake;

abstract class Service
{
    /**
     * the event queue that is dedicated to the service
     */

    private $event_queue;

    /**
     * the event execution result
     */
    private $event_execution_result;

    /**
     * the service constructor
     */
    public function __construct()
    {
        $this->event_queue = array();
    }

    /**
     * register an event callback in the service scope
     */
    public function on($event_name, $callback)
    {
        $this->event_queue[$event_name] = $callback;
    }

    /**
     * trigger an event in the service scope
     */
    public function trigger($event_name, $callback = "", $params = array())
    {

        if($callback == "" && isset($this->event_execution_result[$event_name]) ){
            return $this->event_execution_result[$event_name];
        }
        else{
            if(isset($this->event_queue[$event_name])){
                $callback = $this->event_queue[$event_name];
            }

            $result = "";
            if($callback != ""){
                $result = call_user_func_array($callback, $params);
                $this->event_execution_result[$event_name] = $result;
            } 
            return $result;
        }
    }

    /**
     * start the service
     * @return Service the service object, required for each service
     */
    abstract public function start($app);

}
