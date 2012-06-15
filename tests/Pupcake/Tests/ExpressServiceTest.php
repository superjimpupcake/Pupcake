<?php
namespace Pupcake\Tests;

use Pupcake;

class ExpressServiceTest extends Pupcake\TestCase
{
    public function testHolder()
    {
    }

    public function testExpressSimpleRequest()
    {
        $this->simulateRequest("get", "/date/2012/12/25");

        $app = new Pupcake\Pupcake();

        $app->getService("Pupcake\Service\Express"); //load service

        $app->get("date/:year/:month/:day", function($req, $res){
            $output = $req->params('year').'-'.$req->params('month').'-'.$req->params('day');
            $res->send($output);
        });

        $app->get("hello", function($req, $res){
            $res->send("hello world");
        });

        $app->run();

        $this->assertEquals($this->getRequestOutput(), "2012-12-25");
    }

    public function testExpressServiceOverride()
    {
        $this->simulateRequest("get", "/date/2012/12/25");

        $app = new Pupcake\Pupcake();

        $app->getService("Pupcake\Service\Express"); //load service

        /**
         * override system.request.found event handling, return custom output
         */
        $app->on("system.request.found", function($event) use ($services) {
            return "custom output";
        });


        $app->get("date/:year/:month/:day", function($req, $res){
            $output = $req->params('year').'-'.$req->params('month').'-'.$req->params('day');
            $res->send($output);
        });

        $app->get("hello", function($req, $res){
            $res->send("hello world");
        });

        $app->run();

        $this->assertEquals($this->getRequestOutput(), "custom output");

    }

    public function testExpressServicCustomEventRegistration()
    {
        $this->simulateRequest("get", "/date/2012/12/25");

        $app = new Pupcake\Pupcake();

        $services = array();
        $services['Express'] = $app->getService("Pupcake\Service\Express"); //load service

        /**
         * add back the express service to the event handling
         */
        $app->on("system.request.found", function($event) use ($services) {
            return $event->register($services['Express'])
                ->start(); 
        });

        $app->get("date/:year/:month/:day", function($req, $res){
            $output = $req->params('year').'-'.$req->params('month').'-'.$req->params('day');
            $res->send($output);
        });

        $app->get("hello", function($req, $res){
            $res->send("hello world");
        });

        $app->run();

        $this->assertEquals($this->getRequestOutput(), "2012-12-25");

    }


    public function testExpressRequestForwarding()
    {
        $this->simulateRequest("get", "/test_internal");

        $app = new Pupcake\Pupcake();

        $app->getService("Pupcake\Service\Express");

        $app->get("/hello/:name", function($req, $res){
            $res->send($req->params('name'));
        });
        $app->post("/hello/:name", function($req, $res){
            $res->send("posting ".$req->params('name')." to hello");
        });

        $app->get("test", function($req, $res){
            $res->redirect("test2");
        });

        $app->any("date/:year/:month/:day", function($req, $res){
            $output = $req->params('year')."-".$req->params('month')."-".$req->params('day');
            $res->send($output);
        });

        $app->get("/test2", function($req, $res){
            $res->send("gettest2");
        });

        $app->get("test_internal", function($req, $res){
            $content = "";
            $content .= $res->forward("POST", "hello/world");
            $content .= $res->forward("GET", "hello/world2");
            $content .= $res->forward("GET", "hello/world3");
            $content .= $res->forward("GET", "test");
            $content .= $res->forward("POST", "date/2012/05/30");
            $res->send($content);
        });

        $app->run();

        $this->assertEquals($this->getRequestOutput(), "posting world to helloworld2world3gettest22012-05-30");
    }

    public function testNextRouteMatching()
    {
        $this->simulateRequest("get", "/api/12");
        
        $app = new Pupcake\Pupcake();

        $app->getService("Pupcake\Service\Express"); //load service
        $app->getService("Pupcake\Service\RouteConstraint"); //load service

        $app->any("api/12", function($req, $res, $next){
            $next();
        });

        $app->any("api/:number", function($req, $res, $next){
            $next();
        })->constraint(array(
            'number' => function($value){
                $result = true;
                if($value < 15){
                    $result = false;
                }
                return $result;
            }
        ));

        $app->get("api/12", function($req, $res, $next){
            $next();
        });

        $app->get("api/:number", function($req, $res, $next){
            $res->send("this is finally number ".$req->params('number'));
        });


        $app->run();

        $this->assertEquals($this->getRequestOutput(), "this is finally number 12");
    }
}
