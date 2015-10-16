<?php

/**
 * Swoole封装类
 * @author jiaofuyou@qq.com
 * @date   2014-11-25
 */
class swoole
{
    private $info_dir="/var/local";
    private $pid_file="";
    public $host,$port;
    public $serv;
    public $config;
    public $on_func;
    public $funclist;
    public $is_sington=false;  //是否单例运行，单例运行会在tmp目录下建立一个唯一的PID

    private function my_set_process_name($title)
    {
        if (substr(PHP_VERSION,0,3) >= '5.5') {
            cli_set_process_title($title);
        } else {
            swoole_set_process_name($title);
        }
    }

    function my_onStart($serv)
    {
        global $argv;

        //替换pid为当前进程的pid，因为daemonize模式父进程会被替换
        $this->createPidfile();
        $path_info=pathinfo($argv[0]);
        $title=$path_info['filename'];
        $this->my_set_process_name("{$title}: master");
        Log::prn_log(DEBUG,"MasterPid={$serv->master_pid}|Manager_pid={$serv->manager_pid}");
        Log::prn_log(DEBUG,"Server: start.Swoole version is [".SWOOLE_VERSION."]");
    }

    function my_onManagerStart($serv)
    {
        global $argv;
        $path_info=pathinfo($argv[0]);
        $title=$path_info['filename'];
        $this->my_set_process_name("{$title}: manager");
    }

    function my_onShutdown($serv)
    {
        Log::prn_log(NOTICE,"Server: onShutdown");
        if (file_exists($this->pid_file)){
            unlink($this->pid_file);
            Log::prn_log(DEBUG, "delete pid file " . $this->pid_file);
        }
    }

    function my_onClose($serv, $fd, $from_id)
    {
        global $req_string;
        $req_string[$fd] = '';
        //unset($req_string[$fd]);
        $conninfo=$serv->connection_info($fd);
        Log::prn_log(DEBUG, "WorkerClose: client[$fd@{$conninfo['remote_ip']}]!");
    }

    function my_onConnect($serv, $fd, $from_id)
    {
        $conninfo=$serv->connection_info($fd);
        Log::prn_log(DEBUG, "WorkerConnect: client[$fd@{$conninfo['remote_ip']}]!");
    }

    function my_onWorkerStart($serv, $worker_id)
    {
        global $argv;
        global $db;

        call_user_func($this->on_func['reload'], $this);

        if($worker_id >= $serv->setting['worker_num'])
        {
            $path_info=pathinfo($argv[0]);
            $title=$path_info['filename'];
            $this->my_set_process_name("{$title}: tasker");
            Log::prn_log(DEBUG,"TaskerStart: WorkerId={$serv->worker_id}|WorkerPid={$serv->worker_pid}");
        }
        else
        {
            $path_info=pathinfo($argv[0]);
            $title=$path_info['filename'];
            $this->my_set_process_name("{$title}: worker");
            Log::prn_log(DEBUG,"WorkerStart: WorkerId={$serv->worker_id}|WorkerPid={$serv->worker_pid}");
        }

        $db=new mysqldb(array('host'    => $this->config['server']['mysql_host'],
                              'port'    => $this->config['server']['mysql_port'],
                              'user'    => $this->config['server']['mysql_user'],
                              'passwd'  => $this->config['server']['mysql_passwd'],
                              'name'    => $this->config['server']['mysql_db'],
                              'persistent' => false, //MySQL长连接
        ));
        $db->connect();

        if ( isset($this->on_func['workerstart']) ) call_user_func($this->on_func['workerstart'], $serv, $worker_id);
    }

    function my_onWorkerStop($serv, $worker_id)
    {
        Log::prn_log(NOTICE,"WorkerStop: WorkerId={$serv->worker_id}|WorkerPid=".posix_getpid());
    }

    function my_onWorkerError($serv, $worker_id, $worker_pid, $exit_code)
    {
        Log::prn_log(ERROR,"WorkerError: worker abnormal exit. WorkerId=$worker_id|WorkerPid=$worker_pid|ExitCode=$exit_code");
    }

    function my_onReceive($serv, $fd, $from_id, $data)
    {
        //Log::prn_log(DEBUG, "WorkerReceive: client[$fd@{$serv->connection_info($fd)['remote_ip']}] : \n$data");

        $info = $this->serv->connection_info($fd);
        $protocol=$this->port[$info['from_port']];

        $reqdata=call_user_func($this->on_func['input'], $serv, $fd, $data, $protocol);
        if ( $reqdata === false ) return;
        if ( $reqdata === -1 ) {
            $serv->close($fd);
            return;
        }

        $repdata=call_user_func($this->on_func['request'], $serv, $fd, $from_id, $reqdata, $protocol, $this->funclist);
        //Log::prn_log(DEBUG, "WorkerReponse: client[$fd@{$serv->connection_info($fd)['remote_ip']}] : \n$repdata");
//        if ( $err = error_get_last() != NULL ) {
//            Log::prn_log(ERROR, "[$err['message']] {$err['message']} in {$err['file']} on line {$err['line']}");
//        }
        $serv->send($fd, $repdata);

        return;
    }

    public function __construct($config)
    {
        $this->config['swoole'] = $config;
        $this->host = $this->config['swoole']['swoole_host'];
        $this->port = $this->config['swoole']['swoole_port'];
        $this->is_sington = isset($config['is_sington'])?$config['is_sington']:false;

        Log::$log_level = $config['log_level'];

        if ( is_string($this->port) ) {
            $this->port=array($this->port => 'tcp');
        }
        $i=0;
        foreach($this->port as $port=>$protocol) {
            if ($i==0) {
                $this->serv = new swoole_server($this->host,$port);
            } else {
                $this->serv->addlistener($this->host,$port,SWOOLE_SOCK_TCP);
            }
            $i++;
        }

        $this->serv->set($this->config['swoole']);
        $this->serv->on('Start',        array($this, 'my_onStart'));
        $this->serv->on('Connect',      array($this, 'my_onConnect'));
        $this->serv->on('Receive',      array($this, 'my_onReceive'));
        $this->serv->on('Close',        array($this, 'my_onClose'));
        $this->serv->on('Shutdown',     array($this, 'my_onShutdown'));
        $this->serv->on('WorkerStart',  array($this, 'my_onWorkerStart'));
        $this->serv->on('WorkerStop',   array($this, 'my_onWorkerStop'));
        $this->serv->on('WorkerError',  array($this, 'my_onWorkerError'));
        $this->serv->on('ManagerStart', array($this, 'my_onManagerStart'));
    }

    public function on($event, $func)
    {
        $this->on_func[$event]=$func;
    }

    public function reload_set($config){
        $this->config['server'] = $config['server'];
        $this->funclist = $config['funclist'];

        Log::$log_level = $config['log_level'];
        Log::prn_log(DEBUG, 'log_level change to '.Log::$log_level);

        Log::prn_log(DEBUG, 'register funclist; ');
        foreach ($config['funclist'] as $key => $value) {
            Log::prn_log(DEBUG, "  $key: $value ");
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
            Log::prn_log(NOTICE, "the server is already started");
        }
        else {
            Log::prn_log(WARNING, "the server end abnormally, auto delete pidfile " . $this->pid_file);
            unlink($this->pid_file);
            return;
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

    public function start()
    {
        global $argv;

        $this->pid_file = $this->info_dir . "/" .__CLASS__ . "_" . substr(basename($argv[0]), 0, -4) . ".pid";
        // 只能单例运行
        if ($this->is_sington==true){
            $this->checkPidfile();
        }
        $this->createPidfile();

        if ( !function_exists($this->on_func['reload']) ) {
            Log::prn_log(ERROR, 'on_reload is must by register!');
            exit;
        }
        if ( !function_exists($this->on_func['input']) ) {
            Log::prn_log(ERROR, 'on_input is must by register!');
            exit;
        }
        if ( !function_exists($this->on_func['request']) ) {
            Log::prn_log(ERROR, 'on_request is must by register!');
            exit;
        }

        $this->serv->start();
    }
}
