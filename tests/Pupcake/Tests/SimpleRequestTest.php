<?php
namespace Pupcake\Tests;

require __DIR__."/../TestCase.php";

use Pupcake;

class SimpleRequestTest extends TestCase
{
    public function testSimpleGetRequest()
    {
        $this->simulateRequest("get", "/hello");

        $app = new Pupcake\Pupcake();
        $app->get("hello", function(){
            return "hello world";
        });

        $app->run();

        $this->assertEquals($this->getRequestOutput(), "hello world");
    }

    public function testRequestForwarding()
    {
        $this->simulateRequest("get", "/test_internal");

        $app = new Pupcake\Pupcake();

        $app->get("/hello/:name", function($name){
            return "get $name";
        });

        $app->post("/hello/:name", function($name){
            return "post $name";
        });

        $app->get("test", function() use ($app){
            return $app->redirect("test2");
        });

        $app->any("date/:year/:month/:day", function($year, $month, $day){
            return $year."-".$month."-".$day;
        });

        $app->get("/test2", function(){
            return "testing 2";
        });

        $app->get("test_internal", function() use ($app) {
            $content = "";
            $content .= $app->forward("POST", "hello/1");
            $content .= $app->forward("GET", "hello/2");
            $content .= $app->forward("GET", "hello/3");
            $content .= $app->forward("GET", "test");
            $content .= $app->forward("POST", "date/2012/05/30");
            return $content;
        });

        $app->run();
        
        $this->assertEquals($this->getRequestOutput(), "post 1get 2get 3testing 22012-05-30");
    }
}
