<?php

/**
 * Swoole封装类
 * @author jiaofuyou@qq.com
 * @date   2015-10-25
 * 
 * http://www.swoole.com/
 */

class swoole
{
    public static $info_dir='/var/local/';
    private $title;
    private $pid_file;
    private $protocol;
    private $route;
    private $shutdown=false;
    private $config;  
    public $serv;
    public $mysql;    
    public $on_func;    
    
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
        $this->shutdown = true;
    }

    function my_onClose($serv, $fd, $from_id)
    {
        $this->recv_buf[$fd] = '';
        $this->package_len[$fd] = 0;

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

        $this->mysql = new mysqldb($this->config['mysql']);
        if ( !$this->mysql->connect() ) {
            $this->serv->shutdown();
        }
        
        if ( $this->protocol === 'http' ) {
            $route_conf = isset(Worker_conf::$config['route'])?Worker_conf::$config['route']:[];
            $this->route = new route($route_conf);
        }

        if ( isset($this->on_func['workerstart']) ) call_user_func($this->on_func['workerstart'], $serv, $worker_id);
    }

    public function my_onReceive($serv, $fd, $from_id, $data)
    {
        if ( !isset($this->recv_buf[$fd]) ) $this->recv_buf[$fd] = '';
        if ( !isset($this->package_len[$fd]) ) $this->package_len[$fd] = 0;
        $this->recv_buf[$fd] .= $data;
        
        $parser = $this->protocol.'_protocol';
        
        //如果一个请求里包含多个完整的请求包，则循环处理
        while(true){
            // 当前包的长度已知           
            if($this->package_len[$fd])
            {
                // 数据不够一个包
                if($this->package_len[$fd] > strlen($this->recv_buf[$fd])) break;
            }
            else
            {
                // 获得当前包长
                $this->package_len[$fd] = $parser::input($serv, $fd, $this->recv_buf[$fd]);
                // 数据不够，无法获得包长
                if($this->package_len[$fd] === 0) break;
                elseif($this->package_len[$fd] > 0)
                {
                    // 数据不够一个包
                    if($this->package_len[$fd] > strlen($this->recv_buf[$fd])) break;
                }
                // 包错误
                else
                {
                    log::prn_log(ERROR, 'error package. package_len');
                    $this->recv_buf[$fd] = '';
                    $this->package_len[$fd] = 0;
                    break;
                }
            }

            // 数据足够一个包长
            // 当前包长刚好等于buffer的长度
            if(strlen($this->recv_buf[$fd]) === $this->package_len[$fd])
            {
                $one_request = $this->recv_buf[$fd];
                $this->recv_buf[$fd] = '';                
            }
            else
            {
                // 从缓冲区中获取一个完整的包
                $one_request = substr($this->recv_buf[$fd], 0, $this->package_len[$fd]);
                // 将当前包从接受缓冲区中去掉
                $this->recv_buf[$fd] = substr($this->recv_buf[$fd], $this->package_len[$fd]);
            }
            // 重置当前包长为0
            $this->package_len[$fd] = 0;
            $request = $parser::decode($serv, $fd, $one_request);

            Log::prn_log(NOTICE, "request:");
            var_dump($request);        

            $response = $parser::request($serv, $fd, $request);

            Log::prn_log(NOTICE, "response:");
            var_dump($response);        

            $serv->send($fd, $parser::encode($serv, $fd, $response));
            
            if($this->recv_buf[$fd] === '') break;
        }

        return;
    }
    
    private function response($response, $status, $result, $header = array())
    {
        if ( $status <> 200 ) {
            Log::prn_log(ERROR, "$status $result");
            $response->status($status);
            $result = json_encode(array('error'=>$result, 'status'=>$status));
        }

        Log::prn_log(NOTICE, "RESPONSE $result");

        foreach($header as $key => $val) $response->header($key, $val);
        $response->end($result);    
    }

    function my_onRequest(swoole_http_request $request, swoole_http_response $response)
    {
        //var_dump($request);
        
        $method = $request->server['request_method'];
        $uri = $request->server['request_uri'];
        $content = $request->rawContent();
        
        Log::prn_log(NOTICE, "REQUEST $method $uri $content");
        
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
        
        if ( $content === false ) $content = '';

        if ( ($method === 'POST') and ($content === '') ) 
        {
            Log::prn_log(ERROR, $content);
            return $this->response($response, 415, 'post content is empty!');        
        }
        $obj = new $class($this, $request, $param);
        return $this->response($response, 200, $obj->$fun(), array('Content-Type' => 'application/json'));
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
    
    public function __construct()
    {
        $config = Swoole_conf::$config;
        if ( !isset($config['protocol']) ) $config['protocol'] = 'http';
        if ( !isset($config['is_sington']) ) $config['is_sington'] = true;
        if ( !isset($config['worker_num']) ) $config['worker_num'] = 6;
        if ( !isset($config['daemonize']) ) $config['daemonize'] = true;        
        $this->config = $config;        
        
        $this->pid_file = self::$info_dir . "swoole_{$config['server_name']}.pid";
        $this->title = 'swoole_'.$config['server_name'];
        $this->protocol = $config['protocol'];

        Log::$log_level = $config['log_level'];
        
        $this->trans_listener($config['listen']);

        $i=0;
        foreach($this->listen as $v) {
            if ($i==0) {
                log::prn_log(INFO, "listen: {$v['host']}:{$v['port']}");
                if ( $this->protocol === 'http' ) {
                    log::prn_log(NOTICE, "start http server");
                    $this->serv = new swoole_http_server($v['host'],$v['port']);
                } else {
                    if(!class_exists($this->protocol.'_protocol'))
                    {       
                        log::prn_log(ERROR, "protocol class {$this->protocol} not exist!");
                        exit;
                    }                    
                    log::prn_log(NOTICE, "start tcp({$this->protocol}) server");
                    $this->serv = new swoole_server($v['host'],$v['port']);
                }     
            } else {
                log::prn_log(INFO, "listen: {$v['host']}:{$v['port']}");
                $this->serv->addlistener($v['host'],$v['port'],SWOOLE_SOCK_TCP);
            }
            
            $i++;
        }

        $this->serv->set($this->config);
        $this->serv->on('Start',        array($this, 'my_onStart'));
        $this->serv->on('Connect',      array($this, 'my_onConnect'));        
        $this->serv->on('Close',        array($this, 'my_onClose'));
        $this->serv->on('Shutdown',     array($this, 'my_onShutdown'));
        $this->serv->on('WorkerStart',  array($this, 'my_onWorkerStart'));
        $this->serv->on('WorkerStop',   array($this, 'my_onWorkerStop'));
        $this->serv->on('WorkerError',  array($this, 'my_onWorkerError'));
        $this->serv->on('ManagerStart', array($this, 'my_onManagerStart'));
        if ( $this->protocol === 'http' ) {
            $this->serv->on('Request', array($this, 'my_onRequest'));
        } else {
            $this->serv->on('Receive', array($this, 'my_onReceive'));
        } 
    }

    public function on($event, $func)
    {
        $this->on_func[$event]=$func;
    }

    public function reload_set($config){
        $this->config['mysql'] = $config['mysql'];
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
        if ($this->config['is_sington']==true){
            $this->checkPidfile();
        }
        $this->createPidfile();
        
        $this->serv->start();
        if ( !$this->shutdown) log::prn_log(ERROR, "swoole start error: ".swoole_errno().','.swoole_strerror(swoole_errno()));
    }
}
