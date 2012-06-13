<?php
/**
 * A service event is sent by a main application event
 * It carries over a limited feature of the application event
 */
class ServiceEvent
{
    private $name;

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }
}
