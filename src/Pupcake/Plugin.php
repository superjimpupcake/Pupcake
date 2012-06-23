<?php
namespace Pupcake;

/**
 * The pupcake plugin 
 */
abstract class Plugin
{
    private $app; //the app instance
    private $event_helpers;

    public function __construct()
    {
        $this->event_helpers = array();
    }

    public function setAppInstance($app)
    {
        $this->app = $app;
    }

    public function getAppInstance()
    {
        return $this->app;
    }

    /**
     * use a event handler for an event
     */
    public function on($event_name, $callback)
    {
        $this->app->on($event_name, $callback);
    }

    /**
     * handle an event, same as on
     */
    public function handle($event_name, $callback)
    {
        $this->on($event_name, $callback);
    }

    /**
     * add a helper callback to an event
     */
    public function help($event_name, $callback)
    {
        if(!isset($this->event_helpers[$event_name]))
        {
            $this->event_helpers[$event_name] = $callback;
        }
    }

    /**
     * get the helper callback of a specifc event
     */
    public function getEventHelperCallback($event_name)
    {
        if(isset($this->event_helpers[$event_name])){
            return $this->event_helpers[$event_name];
        }
    }

    /**
     * get all event helper callbacks
     */
    public function getEventHelperCallbacks()
    {
        return $this->event_helpers;
    }

    /**
     * start loading a plugin
     */
    abstract public function load($config = array());
}
