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
$server->start();
