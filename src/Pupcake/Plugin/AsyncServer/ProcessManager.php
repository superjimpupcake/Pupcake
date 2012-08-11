<?php
/**
 * A simple process manager
 * Credit: http://www.php.net/manual/en/function.pcntl-fork.php#98711
 */
namespace Pupcake\Plugin\AsyncServer;

declare(ticks=1){ 
  class ProcessManager
  { 
    private $server; //the server that this process maanger belongs to
    private $jobs; //all jobs
    public $max_processes = 25; //maximum number of processes to handle at one time
    protected $job_started = 0; 
    protected $current_jobs = array(); 
    protected $signal_queue=array();   
    protected $parent_pid; 
    private $process_output; // the ouptut of the processes
    private $process_dir; //the process's directory

    public function __construct($server)
    { 
      $this->process_output = array();
      $this->server = $server;
      $this->parent_pid = getmypid(); 
      pcntl_signal(SIGCHLD, array($this, "childSignalHandler")); 
    } 

    public function setProcessDirectory($process_dir)
    {
      $this->process_dir = $process_dir;
    } 

    public function setMaxNumberOfProcess($max_num_of_processes)
    {
      $this->max_processes = $max_num_of_processes;
    }

    /** 
     * Run the Daemon 
     */ 
    public function run()
    { 
      foreach($this->jobs as $job_id => $job_handler){ 
        while(count($this->current_jobs) >= $this->max_processes){ 
          //Maximum children allowed, waiting...
          sleep(1); 
        } 

        $launched = $this->launchJob($job_id); 
      } 

      //Wait for child processes to finish before exiting here 
      while(count($this->current_jobs)){ 
        //echo "Waiting for current jobs to finish... \n"; 
        sleep(1); 
      } 
    } 

    /**
     * Add a job in the job queue
     */
    public function addProcess($job_id, $job_handler)
    {
      $this->jobs[$job_id] = $job_handler;
    }

    /**
     * get output of a process
     */
    public function getProcessOutput($job_id)
    {
      $result = false;
      $job_output_file = "{$this->process_dir}/$job_id.output";
      if(is_readable($job_output_file)){
        $result = unserialize(file_get_contents($job_output_file));
      }
      return $result;
    }

    /** 
     * Launch a job from the job queue 
     */ 
    protected function launchJob($job_id)
    { 
      $pid = pcntl_fork(); 
      if($pid == -1){ 
        //Problem launching the job 
        //error_log('Could not launch new job, exiting'); 
        return false; 
      } 
      else if ($pid){ 
        // Parent process 
        // Sometimes you can receive a signal to the childSignalHandler function before this code executes if 
        // the child script executes quickly enough! 
        // 
        $this->current_jobs[$pid] = $job_id; 

        // In the event that a signal for this pid was caught before we get here, it will be in our signal_queue array 
        // So let's go ahead and process it now as if we'd just received the signal 
        if(isset($this->signal_queue[$pid])){ 
          echo "found $pid in the signal queue, processing it now \n"; 
          $this->childSignalHandler(SIGCHLD, $pid, $this->signal_queue[$pid]); 
          unset($this->signal_queue[$pid]); 
        } 
      } 
      else{ 
        //Forked child, do your deeds.... 
        $job_handler = $this->jobs[$job_id];
        $output  = $job_handler();
        file_put_contents("{$this->process_dir}/$job_id.output", serialize($output));
        $exitStatus = 0; //Error code if you need to or whatever 
        exit($exitStatus); 
      } 
      return true; 
    } 

    public function childSignalHandler($signo, $pid=null, $status=null)
    { 
      //If no pid is provided, that means we're getting the signal from the system.  Let's figure out 
      //which child process ended 
      if(!$pid){ 
        $pid = pcntl_waitpid(-1, $status, WNOHANG); 
      } 

      //Make sure we get all of the exited children 
      while($pid > 0){ 
        if($pid && isset($this->current_jobs[$pid])){ 
          $exitCode = pcntl_wexitstatus($status); 
          if($exitCode != 0){ 
            echo "$pid exited with status ".$exitCode."\n"; 
          } 
          unset($this->current_jobs[$pid]); 
        } 
        else if($pid){ 
          //Oh no, our job has finished before this parent process could even note that it had been launched! 
          //Let's make note of it and handle it when the parent process is ready for it 
          //Adding $pid to the signal queue .....
          $this->signal_queue[$pid] = $status; 
        } 
        $pid = pcntl_waitpid(-1, $status, WNOHANG); 
      } 
      return true; 
    } 
  }
}

