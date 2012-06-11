<?php
namespace Pupcake;

/**
 * Event handler that is dedicated to handle one event
 */
class EventHandler
{

    private $event;
    private $callbacks;
    private $return_values; //store the return values of the event handler's execution

    public function __construct($event = "")
    {
        $this->event = $event;
        $this->callbacks = array();
        $this->return_values = array();
    }

    public function setEvent($event)
    {
        $this->event = $event;
    }

    public function getEvent()
    {
        return $this->event;
    }

    /**
     * register an array of callbacks to handle the event
     */
    public function register($callbacks = array())
    {
        $this->callbacks = array();
    }

    /**
     * get all callbacks in this event handler
     */
    public function getCallbacks()
    {
        return $this->callbacks;
    }

    /**
     * execute the handler
     */
    public function run()
    {
        if(count($this->callbacks) > 0){
            foreach($this->callbacks as $callback){
                $return_value = call_user_func_array($callback, array($this->event, $this));
                if($return_value === NULL){
                    $this->return_values = NULL;
                }
                else{
                    $this->return_values[] = $return_value;
                }
            }
            if(count($this->return_values) == 1){ //the return value has only 1 value, return that value itself
                $this->return_values = $this->return_values[0];
            }
        }
        return $this->return_values;
    }
}
