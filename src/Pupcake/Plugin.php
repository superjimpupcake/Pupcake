<?php
namespace Pupcake;

/**
 * The pupcake plugin 
 */
class Plugin
{
    private $app; //the app instance
    private $event_helpers;

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
        return $this->app->on($event_name, $callback);
    }

    /**
     * add a helper callback to an event
     */
    public function help($event_name, $callback)
    {

    }
}
