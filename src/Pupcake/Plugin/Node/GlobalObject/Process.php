<?php
namespace Pupcake\Plugin\Node\GlobalObject;

class Process extends GlobalObject
{
  public function nextTick($callback)
  {
    $loop = $this->getNode()->getEventLoop();
    // TO DO
  }
}
