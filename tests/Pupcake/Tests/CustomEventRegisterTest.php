<?php
namespace Pupcake\Tests;

use Pupcake;

class CustomEventRegisterTest extends Pupcake\TestCase
{
    public function testRouteActionAndRouteConstraint()
    {
        $expectations = array();
        $expectations[] = array('input' => '192.168.2.1', 'output' => 'api#iphello world');
        $expectations[] = array('input' => '10.0.0.1', 'output' => 'api#iphello world');
        $expectations[] = array('input' => 'random', 'output' => 'Invalid Request');

        foreach($expectations as $expectation){
            $this->simulateRequest("get", "/api/ip/".$expectation['input']);

            $app = new Pupcake\Pupcake();
            
            $services = array();
            $services['express'] = $app->getService("Pupcake\Service\Express"); //load Express service
            $services['constraint'] = $app->getService("Pupcake\Service\RouteConstraint"); //load RouteConstraint service
            $services['action'] = $app->getService("Pupcake\Service\RouteAction"); //load RouteAction service

            // custom event registration
            $app->on("system.routing.route.create", function($event) use ($services) {
                return $event->register(
                    $services['express'],
                    $services['constraint'],
                    $services['action'],
                    function($event){
                        $event->props('route')->method('hello', function($word){
                            return "hello $word";
                        }); 
                    }
                )->start();
            });

            $app->get("api/ip/:ip", function($req, $res) use ($app) {
                $route = $app->getRouter()->getMatchedRoute();
                $action = $route->getAction();
                $hello = $route->hello('world');
                $res->send($action.$hello);
            })
            ->to("api#ip")
            ->constraint(array(
                'ip' =>  function($value){
                    return \Respect\Validation\Validator::ip()->validate($value);
                }
            ));

            $app->run();

            $this->assertEquals($this->getRequestOutput(), $expectation['output']);
        }

    }
}
