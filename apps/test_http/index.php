<?php

/**
 * PHP Swoole Server守护进程
 * @author jiaofuyou@qq.com
 * @date 2015-10-25
 */

define('BASE_PATH', __DIR__);
require_once BASE_PATH.'/../../lib/autoload.php';

$server = new swoole();
$server->start();
