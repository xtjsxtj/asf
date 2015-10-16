<?php

/**
 * leveldb_server服务程序
 * @author jiaofuyou@qq.com
 */

//error_reporting(E_ALL & ~E_WARNING);  //闭关警告提示

require_once dirname(__FILE__).'/../lib/log.php';
require_once dirname(__FILE__).'/../lib/http.php';

/**
 * $array config=array(
 *   'host' => 'localhost',
 *   'port' => 9510,
 *   'database' => './leveldbs',
 *   'memsize' => 256,
 *   'threads' => 4,
 *   'verbose' => NOTICE,
 *   'daemon' => true,
 *   'logfile' => './leveldb.log',
 * )
 */
function leveldb_server($_config)
{
    global $database;
    global $memsize;

    $config = array(
        'worker_num' => 1,
        'log_file' => './leveldb.log',
        'open_length_check' => true,
        'package_length_type' => 'N',
        'package_length_offset' => 0,
        'package_body_offset' => 0,
        'package_max_length' => 800000,
//        'package_eof' => "\r\n\r\n",  //http协议就是以\r\n\r\n作为结束符的，这里也可以使用二进制内容
//        'open_eof_check' => 1,
    );

    $database=$_config['database'];
    if ( isset($_config['host'])    ) $host=$_config['host']; else $host='0.0.0.0';
    if ( isset($_config['port'])    ) $port=$_config['port']; else $port=9510;
    if ( isset($_config['memsize']) ) $memsize=$_config['memsize']; else $memsize=256;
    if ( isset($_config['threads']) ) $config['reactor_num']=$_config['threads'];
    if ( isset($_config['daemon'])  ) $config['daemonize']=$_config['daemon'];
    if ( isset($_config['logfile']) ) $config['log_file']=$_config['logfile'];

    $log_level=NOTICE;
    if ( isset($_config['verbose']) ) {
        $log_level=$_config['verbose'];
        if ( $log_level == 'ERROR' ) $log_level=ERROR;
        else if ( $log_level == 'WARNING' ) $log_level=WARNING;
        else if ( $log_level == 'NOTICE' ) $log_level=NOTICE;
        else if ( $log_level == 'INFO' ) $log_level=INFO;
        else if ( $log_level == 'DEBUG' ) $log_level=DEBUG;
        else if ( $log_level == 'TRACE' ) $log_level=TRACE;
    }
    Log::$log_level=$log_level;

    $serv = new swoole_server($host, $port);
    $serv->set($config);
    $serv->on('Start', 'my_onStart');
    $serv->on('Connect', 'my_onConnect');
    $serv->on('Receive', 'my_onReceive');
    $serv->on('Close', 'my_onClose');
    $serv->on('Shutdown', 'my_onShutdown');
    $serv->on('WorkerStart', 'my_onWorkerStart');
    $serv->on('WorkerStop', 'my_onWorkerStop');
    $serv->on('WorkerError', 'my_onWorkerError');
    $serv->on('ManagerStart', 'my_onManagerStart');

    $serv->start();
}

function my_set_process_name($title)
{
    if (substr(PHP_VERSION,0,3) >= '5.5') {
        cli_set_process_title($title);
    } else {
        my_set_process_name($title);
    }
}

function my_onStart($serv)
{
    global $argv;
    $path_info=pathinfo($argv[0]);
    $title=$path_info['filename'];
    my_set_process_name("{$title}: master");
    Log::prn_log(DEBUG,"MasterPid={$serv->master_pid}|Manager_pid={$serv->manager_pid}");
    Log::prn_log(DEBUG,"Server: start.Swoole version is [".SWOOLE_VERSION."]");
}

function my_onManagerStart($serv)
{
    global $argv;
    $path_info=pathinfo($argv[0]);
    $title=$path_info['filename'];
    my_set_process_name("{$title}: manager");
}

function my_onShutdown($serv)
{
    Log::prn_log(NOTICE,"Server: onShutdown");
    if (file_exists(pid_file)){
        unlink(pid_file);
        Log::prn_log(DEBUG, "delete pid file " . pid_file);
    }
}

function my_onClose($serv, $fd, $from_id)
{
    global $req_string;
    $req_string[$fd] = '';
    //unset($req_string[$fd]);
    $conninfo=$serv->connection_info($fd);
    Log::prn_log(NOTICE, "WorkerClose: client[$fd@{$conninfo['remote_ip']}]!");
}

function my_onConnect($serv, $fd, $from_id)
{
    $conninfo=$serv->connection_info($fd);
    Log::prn_log(DEBUG, "WorkerConnect: client[$fd@{$conninfo['remote_ip']}]!");
}

function my_onWorkerStop($serv, $worker_id)
{
    Log::prn_log(NOTICE,"WorkerStop: WorkerId={$serv->worker_id}|WorkerPid=".posix_getpid());
}

function my_onWorkerError($serv, $worker_id, $worker_pid, $exit_code)
{
    Log::prn_log(ERROR,"WorkerError: worker abnormal exit. WorkerId=$worker_id|WorkerPid=$worker_pid|ExitCode=$exit_code");
}


function my_onWorkerStart($serv, $worker_id)
{
    global $argv;
    global $ldb;
    global $database;
    global $memsize;

    if ($worker_id == 0) {
        $path_info=pathinfo($argv[0]);
        $title=$path_info['filename'];
        my_set_process_name("{$title}: tasker");
        Log::prn_log(DEBUG,"LeveldbStart: WorkerId={$serv->worker_id}|WorkerPid={$serv->worker_pid}");
        $options = array(
        	'create_if_missing' => true,	// if the specified database didn't exist will create a new one
        	'error_if_exists'	=> false,	// if the opened database exsits will throw exception
        	'paranoid_checks'	=> false,
        	'block_cache_size'	=> 32 * 1024 * 1024,
        	'write_buffer_size' => $memsize * 1024 * 1024,
        	'block_size'		=> 4096,
        	'max_open_files'	=> 1000,
        	'block_restart_interval' => 16,
        	//'compression'		=> LEVELDB_SNAPPY_COMPRESSION,
        	'comparator'		=> NULL,   // any callable parameter which returns 0, -1, 1
        );
        $ldb = new LevelDB($database, $options);
        Log::prn_log(INFO, "leveldb conn ok!");
    }
}

/*
function my_onReceive(swoole_server $serv, $fd, $from_id, $data)
{
    global $ldb;
    global $taskid;
    if ( !isset($taskid) ) $taskid = 0;
    $taskid++;

    Log::prn_log(TRACE, "WorkerReceive: client[$fd@{$serv->connection_info($fd)['remote_ip']}] : \n$data");

    //http协议，单条set，qps=35000，如果二进制协议可以达55000
    $reqdata=http_input($fd, $data);
    if ( $reqdata === false ) return;
    if ( $reqdata === -1 ) {
        $serv->close($fd);
        return;
    }

    Log::prn_log(DEBUG, "http_request: \n".$reqdata);
    $request=http_start_simple($reqdata);

    //key=key&val=val&sync=true
    parse_str($request['query_string'], $req);
    $req['cmd']='set'; //$request['path_api'];
    //$req['key']=sprintf('$016d', $taskid);
    //$req['val']=str_repeat('value', 20);

    $readoptions = array(
    	'verify_check_sum'	=> false,
    	'fill_cache'		=> true,
    	'snapshot'		=> null
    );
    $writeoptions = array(
    	'sync' => isset($req['sync'])?$req['sync']==='true':false,
    );

    $message='ok';
    if ( $req['cmd'] == 'set' ) {
        $result = $ldb->set($req['key'], $req['val'], $writeoptions);
        if ( $result === false ) $message='set failed!';
    } else
    if ( $req['cmd'] == 'get' ) {
        $result = $ldb->get($req['key'], $readoptions);
        if ( $result === false ) $message='get not found!';
    } else
    if ( $req['cmd'] == 'delete' ) {
        $result = $ldb->delete($req['key'], $writeoptions);
        if ( $result === false ) $message='delete failed!';
    } else
    if ( $req['cmd'] == 'inc' ) {
        $result = $ldb->get($req['key'], $readoptions);
        if ( $result === false ) $message='inc.get not found!';
        if ( $result !== false ) {
            $val=intval($result)+intval($req['val']);
            $result = $ldb->set($req['key'], $val, $writeoptions);
            if ( $result === false ) $message='int.set failed!';
        }
    } else
    if ( $req['cmd'] == 'dec' ) {
        $result = $ldb->get($req['key'], $readoptions);
        if ( $result === false ) $message='dec.get not found!';
        if ( $result !== false ) {
            $val=intval($result)-intval($req['val']);
            $result = $ldb->set($req['key'], $val, $writeoptions);
            if ( $result === false ) $message='dec.set failed!';
        }
    } else {
        $result = 'cmd is not support opt!';
    }

    if ( $result === false ) {
        $result=json_encode(array('result_code' => 1, 'result_msg' => $message));
    } else {
        $result=json_encode(array('result_code' => 0, 'result_msg' => $result));
    }

    $result=http_end(array('HTTP/1.0 200 OK'), $result);
    Log::prn_log(DEBUG, "http_reponse: \n$result");

    $serv->send($fd, $result, $from_id);

    return;
}

function my_onReceive(swoole_server $serv, $fd, $from_id, $data)
{
    global $ldb;
    global $taskid;
    if ( !isset($taskid) ) $taskid = 0;
    $taskid++;

    $data=str_replace("\r\n\r\n", '', $data);
    Log::prn_log(DEBUG, "http_request: \n".$data);

    $readoptions = array(
    	'verify_check_sum'	=> false,
    	'fill_cache'		=> true,
    	'snapshot'		=> null
    );
    $writeoptions = array(
    	'sync' => false,
    );

    $tmp=explode(' ', $data);
    if ( substr($tmp[0],0,3) == 'sy_' )  {
      $writeoptions['sync'] = true;
      $req['cmd'] = substr($tmp[0], 3);
    } else {
      $req['cmd'] = $tmp[0];
    }
    $req['key'] = $tmp[1];
    if ( count($tmp) > 2 ) $req['val'] = $tmp[2];

    $message='ok';
    if ( $req['cmd'] == 'set' ) {
        $result = $ldb->set($req['key'], $req['val'], $writeoptions);
        if ( $result === false ) $message='set failed!';
    } else
    if ( $req['cmd'] == 'get' ) {
        $result = $ldb->get($req['key'], $readoptions);
        if ( $result === false ) $message='get not found!';
    } else
    if ( $req['cmd'] == 'del' ) {
        $result = $ldb->delete($req['key'], $writeoptions);
        if ( $result === false ) $message='delete failed!';
    } else
    if ( $req['cmd'] == 'inc' ) {
        $result = $ldb->get($req['key'], $readoptions);
        if ( $result === false ) $message='inc.get not found!';
        if ( $result !== false ) {
            $val=intval($result)+intval($req['val']);
            $result = $ldb->set($req['key'], $val, $writeoptions);
            if ( $result === false ) $message='int.set failed!';
        }
    } else
    if ( $req['cmd'] == 'dec' ) {
        $result = $ldb->get($req['key'], $readoptions);
        if ( $result === false ) $message='dec.get not found!';
        if ( $result !== false ) {
            $val=intval($result)-intval($req['val']);
            $result = $ldb->set($req['key'], $val, $writeoptions);
            if ( $result === false ) $message='dec.set failed!';
        }
    } else {
        $result = 'cmd is not support opt!';
    }

    if ( $result === false ) {
        $result='false '.$message."\r\n\r\n";
    } else
    if ( $result === true ) {
        $result='true ok'."\r\n\r\n";
    } else {
        $result='true '.$result."\r\n\r\n";
    }
    Log::prn_log(DEBUG, "http_reponse: \n$result");

    $serv->send($fd, $result, $from_id);

    return;
}
*/

function my_onReceive(swoole_server $serv, $fd, $from_id, $data)
{
    global $ldb;
    global $taskid;
    if ( !isset($taskid) ) $taskid = 0;
    $taskid++;

    Log::prn_log(DEBUG, "WorkerReceive: client[$fd@{$serv->connection_info($fd)['remote_ip']}] : \n".strtoupper(bin2hex($data)));

    $format_unpack = 'Nlen/Copt/Ckeylen/a*tmp';
    $req = unpack($format_unpack, $data);
    $req['key']=substr($req['tmp'],0,$req['keylen']);
    $req['val']=substr($req['tmp'],$req['keylen']);

    $req['cmd'] = '';
    $req['sync'] = false;
    if ( ($req['opt']&0xF0) == 0x10 ) $req['sync'] = true;
    $opt = $req['opt']&0x0F;
    $cmd = array('set','get','del','inc','dec');
    $req['cmd'] = $cmd[$opt];
    //$req['key']=sprintf('$016d', $taskid);
    //$req['val']=str_repeat('value', 20);

    $readoptions = array(
    	'verify_check_sum'	=> false,
    	'fill_cache'		=> true,
    	'snapshot'		=> null
    );
    $writeoptions = array(
    	'sync' => isset($req['sync'])?$req['sync']==='true':false,
    );

    $message='ok';
    if ( $req['cmd'] == 'set' ) {
        $result = $ldb->set($req['key'], $req['val'], $writeoptions);
        if ( $result === false ) $message='set failed!';
    } else
    if ( $req['cmd'] == 'get' ) {
        $result = $ldb->get($req['key'], $readoptions);
        if ( $result === false ) $message='get not found!';
    } else
    if ( $req['cmd'] == 'del' ) {
        $result = $ldb->delete($req['key'], $writeoptions);
        if ( $result === false ) $message='delete failed!';
    } else
    if ( $req['cmd'] == 'inc' ) {
        $result = $ldb->get($req['key'], $readoptions);
        if ( $result === false ) $message='inc.get not found!';
        if ( $result !== false ) {
            $val=intval($result)+intval($req['val']);
            $result = $ldb->set($req['key'], $val, $writeoptions);
            if ( $result === false ) $message='int.set failed!';
        }
    } else
    if ( $req['cmd'] == 'dec' ) {
        $result = $ldb->get($req['key'], $readoptions);
        if ( $result === false ) $message='dec.get not found!';
        if ( $result !== false ) {
            $val=intval($result)-intval($req['val']);
            $result = $ldb->set($req['key'], $val, $writeoptions);
            if ( $result === false ) $message='dec.set failed!';
        }
    } else {
        $result = false;
        $message = 'cmd is not support opt!';
    }

    if ( $result === false ) {
        $opt=1;
        $val=$message;
    } else {
        $opt=0;
        $val=$result;
    }
    if ( $val === true ) $val = 'true';
    if ( $val === false ) $val = 'false';
    $vallen=strlen($val);
    $len=4+1+$vallen;

    $format_pack = 'NCa'.$vallen;
    $result = pack($format_pack, $len,$opt,$val);

    Log::prn_log(DEBUG, "http_reponse: \n".strtoupper(bin2hex($result)));
    $serv->send($fd, $result, $from_id);

    return;
}
