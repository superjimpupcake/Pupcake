<?php
namespace Pupcake\Plugin\Node;

class Module 
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
