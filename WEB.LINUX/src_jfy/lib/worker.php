<?php

/**
 * PHP Gearman Worker守护进程底层类定义
 * @author jiaofuyou@qq.com
 * @date 2014-07-25
 */
class Worker
{
    private $info_dir="/var/local";
    private $pid_file="";
    private $workers_count=0;
    public $config;
    public $funclist;
    public $on_func;

    //检查环境是否支持pcntl支持
    private function checkPcntl(){
        if ( ! function_exists('pcntl_signal_dispatch')) {
            // PHP < 5.3 uses ticks to handle signals instead of pcntl_signal_dispatch
            // call sighandler only every 10 ticks
            declare(ticks = 10);
        }

        // Make sure PHP has support for pcntl
        if ( ! function_exists('pcntl_signal')) {
            $message = 'PHP does not appear to be compiled with the PCNTL extension.  This is neccesary for daemonization';
            Log::prn_log(ERROR, $message);
            throw new Exception($message);
        }
        //信号处理
        pcntl_signal(SIGTERM, array(__CLASS__, "signalHandler"),false);
        pcntl_signal(SIGINT, array(__CLASS__, "signalHandler"),false);
        pcntl_signal(SIGQUIT, array(__CLASS__, "signalHandler"),false);

        // Enable PHP 5.3 garbage collection
        if (function_exists('gc_enable'))
        {
            gc_enable();
            $this->gc_enabled = gc_enabled();
        }
    }

    //--检测pid是否已经存在
    private function checkPidfile(){

        if (!file_exists($this->pid_file)){
            return true;
        }
        $pid = file_get_contents($this->pid_file);
        $pid = intval($pid);
        if ($pid > 0 && posix_kill($pid, 0)){
            Log::prn_log(NOTICE, "the daemon process is already started");
        }
        else {
            Log::prn_log(NOTICE, "the daemon proces end abnormally, please check pidfile " . $this->pid_file);
        }
        exit(1);

    }

    //----创建pid
    private function createPidfile(){

        if (!is_dir($this->info_dir)){
            mkdir($this->info_dir);
        }
        $fp = fopen($this->pid_file, 'w') or die("cannot create pid file");
        fwrite($fp, posix_getpid());
        fclose($fp);
        Log::prn_log(DEBUG, "create pid file " . $this->pid_file);
    }

    //信号处理函数
    private function signalHandler($signo){
        switch($signo){
            //子进程结束信号
            case SIGCHLD:
                while(($pid=pcntl_waitpid(-1, $status, WNOHANG)) > 0){
                    Log::prn_log(NOTICE, "one proc terminal," . $pid);
                    //$this->workers_count --;
                    $this->mainQuit();
                }
            break;
            //中断进程
            case SIGTERM:
            case SIGHUP:
            case SIGQUIT:
                $this->mainQuit();
            break;
            default:
            return false;
        }

    }

    //整个进程退出
    private function mainQuit(){

        if (file_exists($this->pid_file)){
            unlink($this->pid_file);
            Log::prn_log(DEBUG, "delete pid file " . $this->pid_file);
        }
        Log::prn_log(NOTICE, "daemon process exit now");
        posix_kill(0, SIGKILL);
        exit(0);
    }

    public function __construct($config, $funclist){
        Log::$log_level=$this->config['log_level'];
        $this->config = $config;
        $this->funclist = $funclist;
        $this->checkPcntl();
    }

    public function on($event, $func)
    {
        $this->on_func[$event]=$func;
    }

    /**
    *开始开启进程
    *$workers_num 准备开启的进程数
    */
    public function start(){

        {
            global $argv;

            set_time_limit(0);

            // 只允许在cli下面运行
            if (php_sapi_name() != "cli"){
                die("only run in command line mode\n");
            }

            // 只能单例运行
            if ($this->config['is_sington']==true){
                if ( isset($this->config['pid_file']) ) {
                    $this->pid_file = $this->config['pid_file'];
                } else {
                    $this->pid_file = $this->info_dir . "/" .__CLASS__ . "_" . substr(basename($argv[0]), 0, -4) . ".pid";
                }
                $this->checkPidfile();
            }

            if ($this->config['is_sington']==true){
                $this->createPidfile();
            }
        }

        Log::prn_log(INFO, "daemon process is running now");
        pcntl_signal(SIGTERM, array(__CLASS__, "signalHandler"),false);
        pcntl_signal(SIGCHLD, array(__CLASS__, "signalHandler"),false); // if worker die, minus children num

        while (true) {
            if (function_exists('pcntl_signal_dispatch')){
                pcntl_signal_dispatch();
            }

            $pid=-1;
            if($this->workers_count<$this->config['workers_num']){
                $pid=pcntl_fork();
            }

            if($pid>0){
                $this->workers_count++;
            }elseif($pid==0){
                // 这个符号表示恢复系统对信号的默认处理,不然SIGCHLD将捕获不到
                pcntl_signal(SIGTERM, SIG_DFL);
                pcntl_signal(SIGCHLD, SIG_DFL);

                Log::prn_log(DEBUG, "fork proc ok!");
                $this->startWorker();

            }else{

                sleep(2);
            }
        }

        $this->mainQuit();
        exit(0);

    }

    private function startWorker()
    {
        if ( isset($this->on_func['workerstart']) ) call_user_func($this->on_func['workerstart'], $this);

        $mysql_drive = 'mysqlii';
        if ( isset($this->config['mysql_drive']) ) $mysql_drive = $this->config['mysql_drive'];
        if ( $mysql_drive == 'mysqlii' ) {
            global $db;
            $db=new mysqldb(array('host'    => $this->config['mysql_host'],
                                  'port'    => $this->config['mysql_port'],
                                  'user'    => $this->config['mysql_user'],
                                  'passwd'  => $this->config['mysql_passwd'],
                                  'name'    => $this->config['mysql_db'],
                                  'persistent' => false, //MySQL长连接
            ));
            $db->connect();
        }

        $gmworker= new GearmanWorker();
        try {
          if ( !$gmworker->addServer($this->config['gearman_host'], $this->config['gearman_port']) ) {
            Log::prn_log(ERROR, 'gearman addserver failed!');
            $this->mainQuit();
          }
        } catch(Exception $e) {
          Log::prn_log(ERROR, 'gearman addserver exception!');
          $this->mainQuit();
        }
        Log::prn_log(INFO, "gearman init ok ({$this->config['gearman_host']},{$this->config['gearman_port']})!");

        foreach($this->funclist as $key => $value) {
            if (!is_string($key)) {
                Log::prn_log(ERROR, "function_name: $key is not string!");
                $this->mainQuit();
            }
            $gmworker->addFunction($key, $value);
        }

        Log::prn_log(INFO, "waiting for job ...");
        $cnt=0;
        while(true)
        {
          if ($cnt >= 10) {
            Log::prn_log(ERROR, "gearman worker() failed 10 times, quit!");
            $this->mainQuit();
          }
          try {
            if ( !@$gmworker->work() ) {
              $cnt++;
              Log::prn_log(NOTICE, "gearman worker() failed, sleep 5s, again try {$cnt} ...");
              sleep(5);
              continue;
            }
          } catch(Exception $e) {
            $cnt++;
            Log::prn_log(NOTICE, "gearman worker() exception, sleep 5s, again try {$cnt} ...");
            sleep(5);
            continue;
          }
          if ($gmworker->returnCode() != GEARMAN_SUCCESS)
          {
            $cnt++;
            Log::prn_log(NOTICE, "gearman worker() failed return_code: " . $gmworker->returnCode() . ", sleep 5s, again try {$cnt} ...");
            sleep(5);
            continue;
          }
          $cnt=0;
        }
    }
}
