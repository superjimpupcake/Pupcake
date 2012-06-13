<?php
namespace Pupcake\Tests;

use Pupcake;

class MultipleServiceTest extends Pupcake\TestCase
{
    public function testRouteActionAndRouteConstraint()
    {
        $expectations = array();
        $expectations[] = array('input' => '192.168.2.1', 'output' => 'api#ip');
        $expectations[] = array('input' => '10.0.0.1', 'output' => 'api#ip');
        $expectations[] = array('input' => 'random', 'output' => 'Invalid Request');

        foreach($expectations as $expectation){
            $this->simulateRequest("get", "/api/ip/".$expectation['input']);

            $app = new Pupcake\Pupcake();

            $app->getService("Pupcake\Service\Express"); //load Express service
            $app->getService("Pupcake\Service\RouteConstraint"); //load RouteConstraint service
            $app->getService("Pupcake\Service\RouteAction"); //load RouteAction service

            $app->get("api/ip/:ip", function($req, $res) use ($app) {
                $res->send($app->getRouter()->getMatchedRoute()->getAction());
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
