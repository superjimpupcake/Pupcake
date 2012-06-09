<?php
namespace Pupcake\Tests;

use Pupcake;

class SimpleRequestTest extends Pupcake\TestCase
{
    public function testSimpleGetRequest()
    {
        $this->simulateRequest("get", "/hello");

        $app = new Pupcake\Pupcake();
        $app->get("hello", function(){
            return "hello in get";
        });

        $app->run();

        $this->assertEquals($this->getRequestOutput(), "hello in get");
    }

    public function testSimpleGetRequestWithParams()
    {
        $this->simulateRequest("get", "/hello/world");

        $app = new Pupcake\Pupcake();
        $app->get("hello/:string", function($string){
            return "hello $string in get";
        });

        $app->run();

        $this->assertEquals($this->getRequestOutput(), "hello world in get");
    }

    public function testSimplePostRequest()
    {
        $this->simulateRequest("post", "/hello");

        $app = new Pupcake\Pupcake();
        $app->post("hello", function(){
            return "hello in post";
        });

        $app->run();

        $this->assertEquals($this->getRequestOutput(), "hello in post");
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
