<?php
namespace Pupcake\Plugin\Node\GlobalObject;

class Console
{
  public function log($message)
  {
    print $message."\n";
  }
}
