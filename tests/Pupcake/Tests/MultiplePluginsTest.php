<?php
namespace Pupcake\Tests;

use Pupcake;

class MultiplePluginsTest extends Pupcake\TestCase
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

            $app->usePlugin("Pupcake\Plugin\Express"); //load Express Plugin
            $app->usePlugin("Pupcake\Plugin\RouteConstraint"); //load RouteConstraint Plugin
            $app->usePlugin("Pupcake\Plugin\RouteAction"); //load RouteAction Plugin

            $app->get("api/ip/:ip", function($req, $res) use ($app) {
                $res->send($app->getRouter()->getMatchedRoute()->getAction());
            })
            ->to("api#ip")
            ->constraint(array(
                'ip' =>  function($value){
                    $value_comps = explode(".", $value);
                    if(count($value_comps) == 4){
                        return true;
                    }
                    else{
                        return false;
                    }
                }
            ));

            $app->run();

            $this->assertEquals($this->getRequestOutput(), $expectation['output']);
        }

    }
}
