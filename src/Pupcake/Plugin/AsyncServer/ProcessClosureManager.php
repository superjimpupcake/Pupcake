<?php
namespace Pupcake\Plugin\AsyncServer;

class ProcessClosureManager
{
  private $server; //the server instance the process manager belongs to
  private $processes; //all processes
  private $running_processes; //all running processes
  private $max_num_of_processes;
  private $process_dir;
  private $loop;

  /**
   * constructor of the process closure manager
   * @param the server instance that the process closure manager belongs to
   */
  public function __construct($server)
  {
    $this->server = $server;
    $this->max_num_of_processes = 8;
    $this->processes = array();
    $this->running_processes = array();
    $this->process_dir = null;
    $this->prepareProcessLoop();
  }

  public function setMaxNumberOfProcess($max_num_of_processes)
  {
    $this->max_num_of_processes = $max_num_of_processes;
  }

  public function setProcessDirectory($process_dir)
  {
    $this->process_dir = $process_dir;
  }

  public function getProcessesDirectory()
  {
    return $this->process_dir;
  }

  public function getMaxNumberOfProcesses()
  {
    return $this->max_num_of_processes;
  }

  public function getLoop()
  {
    return $this->loop;
  }

  private function prepareProcessLoop()
  {
    $loop = uv_default_loop();
    $this->loop = $loop;
    $timer = uv_timer_init();

    $pm = $this;

    //start the timer loop
    uv_timer_start($timer, 10, 10, function($stat) use ($timer, $loop, $pm){
      $processes = $pm->getProcesses();
      $running_processes = $pm->getRunningProcesses();
      if(count($processes) > 0){
        foreach($processes as $process_name => $process_info){
          $result = $pm->runProcess($process_name);
          if(!$result){
            break;
          }
        }
        
        if(count($running_processes) > 0){ 
          foreach($running_processes as $process_name => $process_info){
            if($pm->isProcessCompleted($process_name)){
              $pm->removeRunningProcess($process_name);
            }
          }
        }

      }
      else{ //now all processes are done, stop the timeer
        uv_timer_stop($timer);
        uv_unref($timer);
      }
    });

  }

  public function addProcess($process_name, $process_main_callback)
  {
    if(!isset($this->processes[$process_name])){
      $this->processes[$process_name] = array('code' => $process_main_callback);
    }
  }

  /**
   * start running a process
   */
  public function runProcess($process_name)
  {
    if(count($this->running_processes) < $this->max_num_of_processes && !isset($this->running_processes[$process_name])){ //we need to make sure the running process queue is not full also
      $closure = new ProcessClosure($this->processes[$process_name]['code']);
      $code = $closure->getCode();
      $code_output = "";
      $code_output .= "<?php\n";
      $code_output .= "\$func=$code;\n"."echo \$func();\nfile_put_contents(__DIR__.\"/$process_name.result\", 1);\n";
      file_put_contents($this->process_dir."/$process_name.php", $code_output);
      $pid = exec("nohup php {$this->process_dir}/$process_name.php > {$this->process_dir}/$process_name.output 2>/dev/null & echo $!");
      if((int)$pid > 0){
        $this->running_processes[$process_name]['start_time'] = time();
        //since this process is running, we can now remove it from the process list
        unset($this->processes[$process_name]); 
        return $pid;
      }
      else{
        return false;
      }
    }
    else{
      return false;
    }
  }

  public function getProcessOutput($process_name)
  {
    $result = null;
    $process_result_storage = $this->process_dir."/$process_name.output";
    if(is_readable($process_result_storage)){
      $result = file_get_contents($process_result_storage);
    }
    return $result;
  }

  public function getProcesses()
  {
    return $this->processes;
  }

  public function getRunningProcesses()
  {
    return $this->running_processes;
  }

  public function getProcess($process_name)
  {
    $result = false;
    if(isset($this->processes[$process_name])){
      $result = $this->processes[$process_name];
    }
    return $result;
  }

  public function removeProcess($process_name)
  {
    unset($this->processes[$process_name]);
  }

  public function removeRunningProcess($process_name)
  {
    unset($this->running_processes[$process_name]);
  }

  public function isProcessCompleted($process_name)
  {
    $result = false;
    if(is_readable("{$this->process_dir}/$process_name.result")){
      $content = file_get_contents("{$this->process_dir}/$process_name.result");
      if($content == 1){
        $result = true;
      }
    }
    return $result;
  }

  //public function run()
  //{
    //uv_run();
  //}
}
