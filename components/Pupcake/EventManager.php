<?php

namespace Pupcake;

class EventManager
{
    /**
     * @var array
     * Pupcake Event Queue
     */
    private $event_queue; 

    public function __construct()
    {
        $this->event_queue = array();
    }

    public static function instance()
    {
        static $instance;
        if(!isset($instance)){
            $instance = new static();
        }
        return $instance; 
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
        if(isset($this->event_queue[$event_name])){
            $callback = $this->event_queue[$event_name];
        }

        if($callback == ""){
            return "";
        }
        else{
            return call_user_func_array($callback, $params);
        } 
    }
}


