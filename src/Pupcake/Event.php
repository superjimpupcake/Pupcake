<?php
namespace Pupcake;

/**
 * an abstraction on an event
 */
class Event
{
    private $name; //name of the event
    private $properties; //properties of the event
    private $handler_callback; //the main handler callback for this event, one event => one handler callback
    private $service_callbacks; //the service callbacks for this event, one event => many service callbacks
    private $handler_callback_return_value; //store handler callback's return value

    public function __construct($name = "")
    {
        $this->name = $name;
        $this->properties = array();
        $this->service_callbacks = array();
        $this->handler_callback = null;
        $this->handler_callback_return_value = null;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setProperty($key, $val)
    {
        $this->properties[$key] = $val;
    }

    public function setProperties($properties)
    {
        $this->properties = $properties;
    }

    public function getProperty($key)
    {
        return $this->properties[$key];
    }

    public function getProperties()
    {
        return $this->properties;
    }

    public function props($key = "")
    {
        if($key == ""){
            return $this->getProperties();
        }
        else{
            return $this->getProperty($key);
        }
    }

    public function setHandlerCallback($handler_callback)
    {
        $this->handler_callback = $handler_callback;
    }

    public function getHandlerCallback()
    {
        return $this->handler_callback;
    }

    /**
     * register an array of service callbacks to handle the event
     */
    public function register($service_callbacks = array())
    {
        $this->service_callbacks = $service_callbacks;
        return $this->run();
    }

    public function setHandlerCallbackReturnValue($handler_callback_return_value)
    {
        $this->handler_callback_return_value = $handler_callback_return_value;
    }

    public function getHandlerCallbackReturnValue()
    {
        return $this->handler_callback_return_value;
    }

    /**
     * get all service callbacks
     */
    public function getServiceCallbacks()
    {
        return $this->service_callbacks;
    }

    /**
     * run this event
     */
    private function run()
    {
        $result = array();
        if(count($this->service_callbacks) > 0){
            foreach($this->service_callbacks as $callback){
                $return_value = call_user_func_array($callback, array($this));
                $result[] = $return_value;
            }
        }
        if(count($result) == 1){ //if the array has only 1 elment, return it
            $result = $result[0];
        }
        return $result;
    }
}
