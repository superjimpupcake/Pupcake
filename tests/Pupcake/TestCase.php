<?php
namespace Pupcake\Tests;

class TestCase extends \PHPUnit_Framework_TestCase
{
    protected function setupServerEnvironment()
    {
        //find autoloader
        $relative_dir = "/";
        $found = false;
        $path = "";
        for($k=1;$k<=10;$k++){
            $path = __DIR__.$relative_dir."vendor/autoloader.php";
            if(is_readable($path)){
                $found = true;
                break;
            }
            else{
                $relative_dir .= "../";
            }
        }
        if($found){
            ob_start();
            require $path;
        }
    }

    protected function simulateRequest($request_type, $uri)
    {
        $_SERVER['PHP_SELF'] = "/index.php".$uri;
        $_SERVER['REQUEST_METHOD'] = strtoupper($request_type);
        $_SERVER['PATH_INFO'] = $uri;
    }

    protected function getRequestOutput()
    {
        $output = ob_get_contents();
        ob_end_clean();
        return $output;
    }
}
