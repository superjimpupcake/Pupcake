<?php
namespace Pupcake\Plugin\Node\GlobalObject;

class Process extends GlobalObject
{
  private $tick_callback;
  private $tick;

  public function nextTick($callback)
  {
    $this->tick_callback = $callback;
    $loop = $this->getNode()->getEventLoop();

    if(!isset($this->tick)){
      $this->tick = uv_async_init($loop, array($this, 'tickAsyncCallback'));
    }
    uv_async_send($this->tick);
  }

  private function tickAsyncCallback($r, $status){
    $tick_callback = $this->tick_callback;
    $tick_callback();
  }
}
