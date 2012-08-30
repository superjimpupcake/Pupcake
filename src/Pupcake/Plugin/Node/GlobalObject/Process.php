<?php
namespace Pupcake\Plugin\Node\GlobalObject;

class Process extends GlobalObject
{
  private $tick_callback;
  private $tick;

  public function nextTick($callback)
  {
    $loop = $this->getNode()->getEventLoop();

    $this->tick = uv_async_init($loop, function($r, $status) use ($callback){
      $callback();
    });

    uv_async_send($this->tick);
  }
}
