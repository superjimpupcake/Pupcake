<?php
namespace Pupcake\Tests;

use Pupcake;

class ArbitaryEventTest extends Pupcake\TestCase
{

    public function testHolder()
    {
    }

    public function testRandomEventTheLegacyWay()
    {

        $this->simulateRequest("get", "/dummy");

        $app = new Pupcake\Pupcake();
        $app->on("service.anything", function(){
            $s = new Pupcake\Object();
            $s->method('hello', function($word){
                return "hello $word";
            });
            return $s;
        });

        $app->get("dummy", function() use ($app){
            $service = $app->trigger("service.anything");
            $word = $service->hello("test");
            return $word;
        });

        $app->run();

        $this->assertEquals($this->getRequestOutput(), "hello test");
    } 

    public function testArbitaryServiceEvent()
    {
        $expectations = array();
        $expectations[] = array('input' => 12, 'output' => 'number detected');
        $expectations[] = array('input' => 'test@test.com', 'output' => 'email detected');
        $expectations[] = array('input' => '192.168.0.1', 'output' => 'ip detected');
        $expectations[] = array('input' => 'random', 'output' => 'Invalid Request');

        foreach($expectations as $expectation){
            $this->simulateRequest("get", "/hello/".$expectation['input']);

            /**
             * First, we need to make sure Respect/Validation package is installed properly via composer
             */
            $app = new Pupcake\Pupcake();

            $app->on('service.validation', function(){
                $validator = array();
                foreach(array('numeric','email','ip') as $type){
                    $validator[$type] = call_user_func("Respect\Validation\Validator::$type");
                }
                return $validator;
            });

            $app->get("hello/:string", function($string) use ($app){
                $validator = $app->trigger('service.validation');
                if($validator['numeric']->validate($string)){
                    return "number detected";
                }
                else if($validator['email']->validate($string)){
                    return "email detected";
                }
                else if($validator['ip']->validate($string)){
                    return "ip detected";
                }
                else{
                    return "Invalid Request";
                }

            });
            $app->run();

            $this->assertEquals($this->getRequestOutput(), $expectation['output']);
        }
    }
}
