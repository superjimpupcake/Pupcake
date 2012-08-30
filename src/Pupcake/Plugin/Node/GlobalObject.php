<?php
namespace Pupcake\Plugin\Node;

class GlobalObject
{
  private $node;

  public function __construct($node)
  {
    $this->node = $node; 
  }

  public function getNode()
  {
    return $this->node; 
  }
}

