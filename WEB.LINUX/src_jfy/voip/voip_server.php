<?php

/**
 * PHP Swoole Server守护进程
 * @author jiaofuyou@qq.com
 * @date 2014-10-25
 */

//error_reporting(E_ALL & ~E_WARNING);  //闭关警告提示
//php --re swoole | grep VERSION
//netstat -tunpl

require_once dirname(__FILE__).'/../lib/swoole.php';
require_once dirname(__FILE__).'/../lib/http.php';
require_once dirname(__FILE__).'/../lib/log.php';
require_once dirname(__FILE__).'/../lib/mysql.php';
require_once dirname(__FILE__).'/./conf/server_conf.php';

//set_error_handler('error_handler');

$server = new swoole(Swoole_conf::$config);
$server->on('reload', 'reload');
$server->on('workerstart', 'workerstart');
$server->on('input', 'input');
$server->on('request', 'request');
$server->start();

function reload($server)
{
    require_once dirname(__FILE__).'/./conf/worker_conf.php';

    $server->reload_set(Worker_conf::$config);

    Log::prn_log(DEBUG, 'reload ok!');
}

function workerstart($serv,$worker_id)
{
    //
}

function input($serv, $fd, $data, $protocol)
{
    return proc_input($serv, $fd, $data, $protocol);
}

function request($serv, $fd, $from_id, $data, $protocol, $funclist)
{
    return proc_request($serv, $fd, $from_id, $data, $protocol, $funclist);
}
