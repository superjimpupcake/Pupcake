<?php
namespace Pupcake\Tests;

use Pupcake;

class ArbitaryEventTest extends Pupcake\TestCase
{

    public function testHolder()
    {
    }

    //public function testRandomEventTheLegacyWay()
    //{

        //$this->simulateRequest("get", "/dummy");

        //$app = new Pupcake\Pupcake();
        //$app->on("service.anything", function(){
            //$s = new Pupcake\Object();
            //$s->method('hello', function($word){
                //return "hello $word";
            //});
            //return $s;
        //});

        //$app->get("dummy", function() use ($app){
            //$service = $app->trigger("service.anything");
            //$word = $service->hello("test");
            //return $word;
        //});

        //$app->run();

        //$this->assertEquals($this->getRequestOutput(), "hello test");
    //} 
}
