<?php
namespace Pupcake;

class Object
{
    private $methods; //store all undeclared object methods
    private $storage; //built-in object storage

    /**
     * construct
     */
    public function __construct()
    {
        $this->methods = array();
        $this->storage= array();
    }

    public function __call($method_name, $params)
    {
        if (isset($this->methods[$method_name])) {
            return call_user_func_array($this->methods[$method_name], $params); 
        }
    }

    /**
     * define a dynamic method in an object
     */
    final public function method($name, $callback)
    {
        $this->methods[$name] = $callback;
    }

    /**
     * set in key with value in the storage 
     */
    final public function storageSet($key, $val)
    {
        $this->storage[$key] = $val;
    }

    /**
     * get the value of a specific key in the storage
     */
    final public function storageGet($key)
    {
        $result = null;
        if (isset($this->storage[$key])) {
            $result = $this->storage[$key];
        }
        return $result;
    }
}
