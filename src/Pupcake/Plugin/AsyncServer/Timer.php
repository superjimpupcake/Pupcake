<?php
/**
 * a server side timer building on top of php-uv
 */

namespace Pupcake\Plugin\AsyncServer;

class Timer
{
  private $timer;
  private $start_at;
  private $repeat_at;

  public function __construct()
  {
    $this->timer = uv_timer_init();
    $this->start_at = 0; //default as start immediately
  }

  public function startAt($start_at)
  {
    $this->start_at = $start_at;
    return $this;
  }

  public function setInterval($callback, $repeat_at)
  {
    $timer_obj = $this;
    uv_timer_start($this->timer, $this->start_at, $repeat_at, function($stat) use ($callback, &$timer_obj) {
      call_user_func_array($callback, array($timer_obj));
    });
    return $this;
  }

  public function setTimeout($callback, $repeat_at)
  {
    $timer_obj = $this;
    $timer = $this->timer;
    uv_timer_start($this->timer, $this->start_at, $repeat_at, function($stat) use ($callback, &$timer_obj, $timer) {
      call_user_func_array($callback, array($timer_obj));
      $timer_obj->stop();
    });
    return $this;
  }

  public function clearInterval()
  {
    uv_timer_stop($this->timer);
    uv_unref($this->timer);
  }
}
