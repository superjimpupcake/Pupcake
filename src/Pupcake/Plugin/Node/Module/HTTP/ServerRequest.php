<?php
namespace Pupcake\Plugin\Node\Module\HTTP;

class ServerRequest
{

  private $url;

  public function getURL()
  {
    return $_SERVER['PATH_INFO'];
  }
} 
