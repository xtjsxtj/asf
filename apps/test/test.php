<?php

/**
 * PHP Swoole Server守护进程
 * @author jiaofuyou@qq.com
 * @date 2015-10-25
 */

define('BASE_PATH', __DIR__);
define('LIB_PATH', BASE_PATH.'/../../lib');

require_once LIB_PATH.'/swoole.php';
require_once LIB_PATH.'/log.php';
require_once LIB_PATH.'/mysql.php';
require_once BASE_PATH.'/config/server_conf.php';

$server = new swoole(Swoole_conf::$config);
$server->on('reload', 'reload');
$server->on('workerstart', 'workerstart');
$server->start();

function  __autoload($className) {  
    log::prn_log(INFO, 'require_once: '.BASE_PATH.'/controller' . "/$className.php");
    require_once BASE_PATH.'/controller' . "/$className.php";
} 

function reload($server)
{
    require_once __DIR__.'/config/worker_conf.php';
       
    $server->reload_set(Worker_conf::$config);

    Log::prn_log(DEBUG, 'reload ok!');
}

function workerstart($serv,$worker_id)
{
    //
}
