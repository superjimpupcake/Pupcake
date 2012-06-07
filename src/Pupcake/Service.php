<?php

/**
 * The Pupcake service class
 */
namespace Pupcake;

abstract class Service
{
    /**
     * start the service
     * @return Service the service object
     */
    abstract public function start($app);
}
