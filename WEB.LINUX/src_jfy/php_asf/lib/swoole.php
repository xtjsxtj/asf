<?php

/**
 * Swoole封装类
 * @author jiaofuyou@qq.com
 * @date   2014-11-25
 */

class swoole
{
    public static $info_dir='/var/local/';
    private $title;
    private $pid_file;
    private $listen;    
    private $is_sington=false;  //是否单例运行，单例运行会在tmp目录下建立一个唯一的PID
    private $server_type;
    private $route;
    protected $config;        
    public $serv;
    public $on_func;
    public $M;    
    
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
        //替换pid为当前进程的pid，因为daemonize模式父进程会被替换
        $this->createPidfile();
        $this->my_set_process_name("{$this->title}: master");
        Log::prn_log(DEBUG,"MasterPid={$serv->master_pid}|Manager_pid={$serv->manager_pid}");
        Log::prn_log(DEBUG,"Server: start.Swoole version is [".SWOOLE_VERSION."]");
    }

    function my_onManagerStart($serv)
    {
        $this->my_set_process_name("{$this->title}: manager");
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
        require_once BASE_PATH.'/config/worker_conf.php';
        $this->reload_set(Worker_conf::$config);
        Log::prn_log(DEBUG, 'reload ok!');        

        if($worker_id >= $serv->setting['worker_num'])
        {            
            $this->my_set_process_name("{$this->title}: tasker");
            Log::prn_log(DEBUG,"TaskerStart: WorkerId={$serv->worker_id}|WorkerPid={$serv->worker_pid}");
        }
        else
        {
            $this->my_set_process_name("{$this->title}: worker");
            Log::prn_log(DEBUG,"WorkerStart: WorkerId={$serv->worker_id}|WorkerPid={$serv->worker_pid}");
        }

        $this->M = new mysqldb(array('host'    => $this->config['server']['mysql_host'],
                              'port'    => $this->config['server']['mysql_port'],
                              'user'    => $this->config['server']['mysql_user'],
                              'passwd'  => $this->config['server']['mysql_passwd'],
                              'name'    => $this->config['server']['mysql_db'],
                              'persistent' => false, //MySQL长连接
        ));
        $this->M->connect();
        
        $this->route = new http_route(Worker_conf::$route_config);

        if ( isset($this->on_func['workerstart']) ) call_user_func($this->on_func['workerstart'], $serv, $worker_id);
    }

    public function my_onReceive($serv, $fd, $from_id, $data)
    {
        //Log::prn_log(DEBUG, "WorkerReceive: client[$fd@{$serv->connection_info($fd)['remote_ip']}] : \n$data");
        $reqdata=call_user_func($this->on_func['input'], $serv, $fd, $from_id, $data);
        if ( $reqdata === false ) return;
        if ( $reqdata === -1 ) {
            $serv->close($fd);
            return;
        }        
        
        $request = [
            'conninfo' => $this->serv->connection_info($fd),
            'fd' => $fd,
            'from_id' => $from_id,
            'content' => $reqdata['content'],  //这个字段值由input中处理，该值会再传入request中处理
        ];

        Log::prn_log(NOTICE, "request:");
        echo "$request\n";        
        
        $response=call_user_func($this->on_func['request'], $serv, $request, NULL);
 
        //Log::prn_log(DEBUG, "WorkerReponse: client[$fd@{$serv->connection_info($fd)['remote_ip']}] : \n$response");
        $serv->send($fd, $response);

        return;
    }
    
    private function response($response, $status, $result, $header = array())
    {
        if ( $status <> 200 ) {
            Log::prn_log(ERROR, "$status $result");
            $response->status($status);
            $result = json_encode(array('error'=>$result, 'status'=>$status));
        }

        Log::prn_log(NOTICE, "response:");
        echo "$result\n";

        foreach($header as $key => $val) $response->header($key, $val);
        $response->end($result);    
    }

    function my_onRequest(swoole_http_request $request, swoole_http_response $response)
    {
        //var_dump($request);
        
        $method = $request->server['request_method'];
        $uri = $request->server['request_uri'];
        
        Log::prn_log(NOTICE, "request:");
        echo "$method $uri\n";
        
//        if ( $request->server['request_method'] <> 'POST' ) {
//            return $this->response($response, 405, 'Method Not Allowed, ' . $request->server['request_method']);     
//        }
//        if ( !preg_match('#^/(\w+)/(\w+)$#', $uri, $match) ) {
//            return $this->response($response, 404, "'$uri' is not found!");  
//        }  
//        $class = $match[1].'_controller';
//        $fun = $match[2];
        
        $route_info = $this->route->handel_route($method, $uri);        
        if ( $route_info === 405 ) {
            return $this->response($response, 405, 'Method Not Allowed, ' . $request->server['request_method']);     
        }
        if ( $route_info === 404 ) {
            return $this->response($response, 404, "'$uri' is not found!");  
        }  
        //log::prn_log(DEBUG, json_encode($route_info));
        $class = $route_info['class'].'_controller';
        $fun = $route_info['fun'];
        $param = isset($route_info['param'])?$route_info['param']:[];
                
        //判断类是否存在
        if (! class_exists($class)  || !method_exists(($class),($fun))) {
            return $this->response($response, 404, " class or fun not found class == $class fun == $fun");
        };

        $content = $request->rawContent();
        if ( $content === false ) $content = '';

        if ( ($method === 'POST') and ($content === '') ) 
        {
            Log::prn_log(ERROR, $content);
            return $this->response($response, 415, 'post content is empty!');        
        }        
        echo "\n$content\n";
                
        $obj = new $class($this->serv, $request);
        return $this->response($response, 200, $obj->$fun($param), array('Content-Type' => 'application/json'));
    }
    
    function my_onWorkerStop($serv, $worker_id)
    {
        Log::prn_log(NOTICE,"WorkerStop: WorkerId={$serv->worker_id}|WorkerPid=".posix_getpid());
    }

    function my_onWorkerError($serv, $worker_id, $worker_pid, $exit_code)
    {
        Log::prn_log(ERROR,"WorkerError: worker abnormal exit. WorkerId=$worker_id|WorkerPid=$worker_pid|ExitCode=$exit_code");
    }

    private function trans_listener($listen)
    {
        if(! is_array($listen))
        {
            $tmpArr = explode(":", $listen);
            $host = isset($tmpArr[1]) ? $tmpArr[0] : '0.0.0.0';
            $port = isset($tmpArr[1]) ? $tmpArr[1] : $tmpArr[0];

            $this->listen[] = array(
                'host' => $host,
                'port' => $port,
            );
            return true;
        }
        foreach($listen as $v)
        {
            $this->trans_listener($v);
        }
    }
    
    public function __construct($config)
    {
        $this->config['swoole'] = $config;
        $this->is_sington = isset($config['is_sington'])?$config['is_sington']:false;
        $this->pid_file = self::$info_dir . "swoole_{$config['server_name']}.pid";
        echo $this->pid_file;
        $this->title = 'swoole_'.$config['server_name'];
        $this->server_type = isset($this->config['swoole']['server_type'])?$this->config['swoole']['server_type']:'http';

        Log::$log_level = $config['log_level'];
        
        $this->trans_listener($config['listen']);

        $i=0;
        foreach($this->listen as $v) {
            if ($i==0) {
                log::prn_log(INFO, "listen: {$v['host']}:{$v['port']}");
                if ( $this->server_type === 'http' ) {
                    log::prn_log(NOTICE, "start http server");
                    $this->serv = new swoole_http_server($v['host'],$v['port']);
                } else
                if ( $this->server_type === 'tcp' ) {
                    log::prn_log(NOTICE, "start tcp server");
                    $this->serv = new swoole_server($v['host'],$v['port']);
                } else {
                    log::prn_log(ERROR, "server_type [$this->server_type] error");
                    exit;
                }                
            } else {
                log::prn_log(INFO, "listen: {$v['host']}:{$v['port']}");
                $this->serv->addlistener($v['host'],$v['port'],SWOOLE_SOCK_TCP);
            }
            $i++;
        }

        $this->serv->set($this->config['swoole']);
        $this->serv->on('Start',        array($this, 'my_onStart'));
        $this->serv->on('Connect',      array($this, 'my_onConnect'));        
        $this->serv->on('Close',        array($this, 'my_onClose'));
        $this->serv->on('Shutdown',     array($this, 'my_onShutdown'));
        $this->serv->on('WorkerStart',  array($this, 'my_onWorkerStart'));
        $this->serv->on('WorkerStop',   array($this, 'my_onWorkerStop'));
        $this->serv->on('WorkerError',  array($this, 'my_onWorkerError'));
        $this->serv->on('ManagerStart', array($this, 'my_onManagerStart'));
        if ( $this->server_type === 'http' ) {
            $this->serv->on('Request', array($this, 'my_onRequest'));
        }
        if ( $this->server_type === 'tcp' ) {
            $this->serv->on('Receive', array($this, 'my_onReceive'));
        } 
    }

    public function on($event, $func)
    {
        $this->on_func[$event]=$func;
    }

    public function reload_set($config){
        $this->config['server'] = $config['server'];
        Log::$log_level = $config['log_level'];
        Log::prn_log(DEBUG, 'log_level change to '.Log::$log_level);
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
        if (!is_dir(self::$info_dir)) mkdir(self::$info_dir);
        $fp = fopen($this->pid_file, 'w') or die("cannot create pid file");
        fwrite($fp, posix_getpid());
        fclose($fp);
        Log::prn_log(DEBUG, "create pid file " . $this->pid_file);
    }

    public function start()
    {
        // 只能单例运行
        if ($this->is_sington==true){
            $this->checkPidfile();
        }
        $this->createPidfile();
        
        if ( $this->server_type === 'tcp' ) {
            //input回调函数负责将tcp分段流数据按协议长度组合成一条完整的请求包
            if ( !function_exists($this->on_func['input']) ) {
                Log::prn_log(ERROR, 'on_input is must by register!');
                exit;
            }
            //request回调函数负责解析TCP协议
            if ( !function_exists($this->on_func['request']) ) {
                Log::prn_log(ERROR, 'on_request is must by register!');
                exit;
            } 
        }
        
        $this->serv->start();
    }
}
