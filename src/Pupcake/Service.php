<?php

/**
 * The Pupcake service class
 */
namespace Pupcake;

class Service
{
    /**
     * the event handlers that is dedicated to the service
     */

    private $event_handlers;

    /**
     * the service constructor
     */
    public function __construct()
    {
        $this->event_handlers = array();
    }

    /**
     * register an event callback in the service scope
     */
    public function on($event_name, $callback)
    {
        $this->event_handlers[$event_name] = $callback;
    }

    /**
     * get the service level event handler
     */
    public function getEventHandler($event_name)
    {
        $result = function(){
        };
        if(isset($this->event_handlers[$event_name]){
            $result = $this->event_handlers[$event_name];
        }
        return $result;
    }

    /**
     * start the service
     * @return Service the service object, required for each service
     */
    abstract public function start($app, $config = array());

}
