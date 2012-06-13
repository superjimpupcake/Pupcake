<?php

/**
 * The Pupcake service class
 */
namespace Pupcake;

abstract class Service
{
    /**
     * the event handlers that is dedicated to the service
     */

    private $event_handlers;
    private $context; //the service context
    private $name; // the name of the service

    /**
     * the service constructor
     */
    public function __construct()
    {
        $this->event_handlers = array();
    }

    /**
     * set the service name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * get the service name
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * register an event callback in the service scope
     */
    public function on($event_name, $callback)
    {
        $this->event_handlers[$event_name] = $callback;
    }

    /**
     * get all event handlers
     */
    public function getEventHandlers()
    {
        return $this->event_handlers;
    }

    /**
     * get the service level event handler
     * @param Event the event object
     */
    public function getEventHandler($event)
    {
        $result = function(){
        };
        $event_name = $event->getName();
        if(isset($this->event_handlers[$event_name])){
            $result = $this->event_handlers[$event_name];
        }
        return $result;
    }

    /**
     * set service context, only contains certain methods from the app
     */
    public function setContext($context)
    {
        $this->context = $context;
    }

    /**
     * get the service context
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * start the service
     * @return Service the service object, required for each service
     */
    abstract public function start($config = array());

}
