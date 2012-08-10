<?php
/**
 * A class to abstract process closure
 * process closure is a special closure function that run as a separate process
 */

namespace Pupcake\Plugin\AsyncServer;

class ProcessClosure
{
  private $func;
  private $reflection;

  public function __construct($func) 
  {
    $this->func = $func;
    $this->reflection = new \ReflectionFunction($func);
  }

  /**
   * Get the actual code of the the process closure
   */
  public function getCode()
  {
    $file = new \SplFileObject($this->reflection->getFileName());
    $file->seek($this->reflection->getStartLine()-1);

    // Retrieve all of the lines that contain code for the closure
    $code = '';
    while ($file->key() < $this->reflection->getEndLine())
    {
      $code .= $file->current();
      $file->next();
    }

    $this->code = $code;

    // Only keep the code defining that closure
    $begin = strpos($code, 'function');
    $end = strrpos($code, '}');
    $code = substr($code, $begin, $end - $begin + 1);
    return $code;
  }

  /**
   * add the used variables of the process closure
   */
  public function getUsedVariables()
  {
    // Make sure the use construct is actually used
    $use_index = stripos($this->code, 'use');
    if ( ! $use_index)
      return array();
    // Get the names of the variables inside the use statement
    $begin = strpos($this->code, '(', $use_index) + 1;
    $end = strpos($this->code, ')', $begin);
    $vars = explode(',', substr($this->code, $begin, $end -
      $begin));
    // Get the static variables of the function via reflection
    $static_vars = $this->reflection->getStaticVariables();
    // Only keep the variables that appeared in both sets
    $used_vars = array();
    foreach ($vars as $var)
    {
      $var = trim($var, ' $&amp;');
      $used_vars[$var] = $static_vars[$var];
    }
    return $used_vars;
  }
}
