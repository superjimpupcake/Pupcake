<?php
namespace Pupcake;

class Object
{
    private $methods; //store all undeclared object methods
    private $storage; //built-in object storage

    public function __construct()
    {
        $this->methods = array();
        $this->storage= array();
    }

    public static function instance()
    {
        static $instance;
        if(!isset($instance)){
            $instance = new static();
        }
        return $instance; 
    }

    public function __call($method_name, $params)
    {
        $class_name = get_class($this);
        if(isset($this->methods[$class_name])){
            return call_user_func_array($this->methods[$class_name], $params); 
        }
    }

    final public function method($name, $callback)
    {
        $class_name = get_called_class(); 
        $this->methods[$class_name] = $callback;
    }

    final public function storageSet($key, $val)
    {
        $this->storage[$key] = $val;
    }

    final public function storageGet($key)
    {
        $result = null;
        if(isset($this->storage[$key])){
            $result = $this->storage[$key];
        }
        return $result;
    }
}
