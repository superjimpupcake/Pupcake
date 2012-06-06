<?php
namespace Pupcake;

class Object
{

    private $methods;

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
}

