<?php
namespace Pupcake;

/**
 * an abstraction on an event
 */
class Event
{
    private $name; //name of the event
    private $properties; //properties of the event

    public function __construct($name = "")
    {
        $this->name = $name;
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
}
