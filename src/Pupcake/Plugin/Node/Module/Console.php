<?php
namespace Pupcake\Plugin\Node\Module;

class Console extends \Pupcake\Plugin\Node\Module
{
  public function log($message)
  {
    print $message."\n";
  }
}
