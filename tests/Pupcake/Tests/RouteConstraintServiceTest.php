<?php
namespace Pupcake\Tests;

use Pupcake;

class RouteConstraintServiceTest extends Pupcake\TestCase
{
    public function testHolder()
    {
    }

    public function testExpressSimpleRequest()
    {
        $this->simulateRequest("get", "/api/validate/random");

        $app = new Pupcake\Pupcake();

        $services = array();
        $services['Route.Constraint'] = $app->getService("Pupcake\Service\RouteConstraint");

        //$app->on('system.routing.route.create', function($event) use ($services) {
            //return $event->register(
                //$services['Route.Constraint']
            //)->start();
        //});

        //$app->on('system.routing.route.matched', function($event) use ($services) {
            //return $event->register(
                //$services['Route.Constraint']
            //)->start();
        //});

        $app->get("api/validate/:token", function($token){
            return $token;
        })->constraint(array(
            'token' => function($value){
                return \Respect\Validation\Validator::date('Y-m-d')
                    ->between('1980-02-02', '2015-12-25')
                    ->validate($value);
            }
        ));

        $app->run();

        $this->assertEquals($this->getRequestOutput(), "Invalid Request");

    }
}
