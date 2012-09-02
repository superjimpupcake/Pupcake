<?php
/**
 * A phpunit based test case class to help writing testcase easy
 */
namespace Pupcake;

class TestCase extends \PHPUnit_Framework_TestCase
{
  protected function simulateRequest($request_type, $uri)
  {
    ob_start();
    $_SERVER['PHP_SELF'] = "/index.php".$uri;
    $_SERVER['REQUEST_METHOD'] = strtoupper($request_type);
    $_SERVER['PATH_INFO'] = $uri;
  }

  protected function getRequestOutput()
  {
    $output = ob_get_contents();
    ob_end_clean();
    return $output;
  }
}
