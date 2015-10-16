<?php

//error_reporting(E_ALL & ~E_WARNING);  //闭关警告提示

require_once dirname(__FILE__).'/./func.php';
require_once dirname(__FILE__).'/./http.php';
require_once dirname(__FILE__).'/./procreq.php';

$serv = new swoole_server("0.0.0.0", 9501);  //监听TCP请求
$serv->addlistener('0.0.0.0', 9502, SWOOLE_SOCK_TCP);   //监听HTTP请求
$serv->addlistener('0.0.0.0', 9503, SWOOLE_SOCK_TCP);   //监听TCP.SwooleMan请求
$serv->set(array(
    //'reactor_num' => 1,
    'worker_num' => 1,
    //'open_eof_check' => true,
    //'package_eof' => "\r\n",
    //'ipc_mode' => 2,  //使用系统消息队列, while true;do ipcs -q;sleep 1;done
    //'task_worker_num' => 1  ,
    //'task_ipc_mode' => 3,
    //'dispatch_mode' => 2,  //高并发不要设置成3,争抢模式，因为 Worker进程之间有竞争关系，那就不再是并行了。就像线程加锁了一样
    //'max_connection' => 50000,
    //'daemonize' => 1,
    //'log_file' => './server.log',
    //'heartbeat_idle_time' => 600,   //一个连接如果600秒内未向服务器发送任何数据，此连接将被强制关闭
    //'heartbeat_check_interval' => 60,  //表示每60秒遍历一次所有连接
));

function my_onStart($serv)
{
    global $argv;
    swoole_set_process_name("php {$argv[0]}: master");
    echo "MasterPid={$serv->master_pid}|Manager_pid={$serv->manager_pid}\n";
    echo "Server: start.Swoole version is [".SWOOLE_VERSION."]\n";
}

function my_onShutdown($serv)
{
    echo "Server: onShutdown\n";
}

function my_onTimer($serv, $interval)
{
	//echo microtime(true)."\n";
    prn_log("Server:Timer Call.Interval=$interval");
}

function my_onClose($serv, $fd, $from_id)
{
    //prn_log("Worker#{$serv->worker_pid} Client[$fd@$from_id]: fd=$fd is closed");
}

function my_onConnect($serv, $fd, $from_id)
{
    prn_log("Worker#{$serv->worker_pid} Client[$fd@$from_id]: Connect");
}

function my_onWorkerStart($serv, $worker_id)
{
    global $argv;
    global $db,$mc;

    if($worker_id >= $serv->setting['worker_num'])
    {
        swoole_set_process_name("php {$argv[0]}: task");
        echo "TaskerStart: MasterPid={$serv->master_pid}|Manager_pid={$serv->manager_pid}";
        echo "|WorkerId={$serv->worker_id}|WorkerPid={$serv->worker_pid}\n";
    }
    else
    {
        swoole_set_process_name("php {$argv[0]}: worker");
        echo "WorkerStart: MasterPid={$serv->master_pid}|Manager_pid={$serv->manager_pid}";
        echo "|WorkerId={$serv->worker_id}|WorkerPid={$serv->worker_pid}\n";
    }

//    $db = new mysqli;
//    $db->connect('172.16.18.114', 'root', 'cpyf', 'test');
//    $mc = new Memcache;
//    $mc->connect('localhost', 11211) or die ("Could not connect");
}

function my_onWorkerStop($serv, $worker_id)
{
    echo "WorkerStop[$worker_id]|pid=".posix_getpid().".\n";
}

function my_onWorkerError($serv, $worker_id, $worker_pid, $exit_code)
{
    echo "worker abnormal exit. WorkerId=$worker_id|Pid=$worker_pid|ExitCode=$exit_code\n";
}

function my_onReceive($serv, $fd, $from_id, $data)
{
    prn_log("Worker#{$serv->worker_pid} Client[$fd@$from_id]: received: \n$data");
    $info = $serv->connection_info($fd);
    if($info['from_port'] == 9501) {
        $reqdata=$data; //tcp_input($data);
        if ( $reqdata === "" ) return;
        $repdata=proc_tcp_request($serv, $fd, $reqdata);
    } else
    if($info['from_port'] == 9502) {
        $reqdata=http_input($fd, $data);
        if ( $reqdata === "" ) return;
        $repdata=prco_http_request($serv, $fd, $reqdata);
    } else
    if($info['from_port'] == 9503) {
        $reqdata=$data; //swoole_input($data);
        if ( $reqdata === "" ) return;
        proc_swoole_request($serv, $fd, $reqdata);
    } else {
        prn_log("error port: {$info['from_port']}!");
        $serv->close($fd);
    }
    //prn_log("Worker#{$serv->worker_pid} Client[$fd@$from_id]: sended: \n$repdata");

    return;
}

$serv->on('Start',       'my_onStart');
$serv->on('Connect',     'my_onConnect');
$serv->on('Receive',     'my_onReceive');
$serv->on('Close',       'my_onClose');
$serv->on('Shutdown',    'my_onShutdown');
$serv->on('WorkerStart', 'my_onWorkerStart');
$serv->on('WorkerStop',  'my_onWorkerStop');
$serv->on('WorkerError', 'my_onWorkerError');
$serv->on('ManagerStart', function($serv) {
    global $argv;
    swoole_set_process_name("php {$argv[0]}: manager");
});

$serv->start();
