<?php
namespace Pupcake;

class EventManager extends Object
{
    /**
     * @var array
     * Pupcake Event Queue
     */
    private $event_queue; 
    
    /**
     * @var array
     * @Pupcake Event execution result
     */
    private $event_execution_result;

    public function __construct()
    {
        $this->event_queue = array();
        $this->event_execution_result = array();
    }


    public function getEventQueue()
    {
        return $this->event_queue;
    }

    public function register($event_name, $callback)
    {
        $this->event_queue[$event_name] = $callback;
    }

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
     * clean up all event queues and event result
     */ 
    public function cleanup()
    {
        $this->event_queue = array();
        $this->event_execution_result = array();
    }

}

