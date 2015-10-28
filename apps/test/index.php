<?php

/**
 * PHP Swoole Server守护进程
 * @author jiaofuyou@qq.com
 * @date 2015-10-25
 */

define('BASE_PATH', __DIR__);

require_once BASE_PATH.'/../../lib/autoload.php';
require_once BASE_PATH.'/config/server_conf.php';

$server = new swoole(Swoole_conf::$config);
$server->on('input', 'input');
$server->on('request', 'request');
$server->start();

function  __autoload($className) {  
    $file = BASE_PATH.'/controller' . "/$className.php";
    if ( file_exists($file) ) {
        log::prn_log(INFO, 'require_once: '. $file);
        require_once BASE_PATH.'/controller' . "/$className.php";
    }
} 

function input($serv, $fd, $from_id, $reqdata){
    
}

function request($serv, $fd, $from_id, $request){
    
}
