<?php
namespace Pupcake\Plugin\Node\GlobalObject;

class Console extends \Pupcake\Plugin\Node\GlobalObject
{
  public function log($message)
  {
    print $message."\n";
  }
}
