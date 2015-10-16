<?php

/**
 * PHP Gearman Worker守护进程
 * @author jiaofuyou@qq.com
 * @date 2014-11-25
 */

//error_reporting(E_ALL & ~E_WARNING);   //闭关警告提示

require_once dirname(__FILE__).'/../lib/worker.php';
require_once dirname(__FILE__).'/../lib/log.php';
require_once dirname(__FILE__).'/../lib/mysql.php';
require_once dirname(__FILE__).'/./apply/apply.php';
require_once dirname(__FILE__).'/./apply/boss.php';
require_once dirname(__FILE__).'/./common/pub.php';
require_once dirname(__FILE__).'/./common/func.php';

$worker=new Worker(
    array(
        'is_sington' => false,
        'pid_file' => '/var/local/voip_worker.pid',
        'log_level' => DEBUG,
        'workers_num' => 3,
        'mysql_drive' => 'mysqlii',
        'mysql_host' => '127.0.0.1',
        'mysql_port' => 3306,
        'mysql_user' => 'root',
        'mysql_passwd' => 'cpyf',
        'mysql_db' => 'voip',
        'gearman_host' => '127.0.0.1',
        'gearman_port' => 4730,
    ),
    array(
        'create_user' => 'apply_with_tran',
        'get_user' => 'apply_with_common',
        'recharge' => 'apply_with_tran',
    )
);
$worker->on('workerstart', 'workerstart');
$worker->start();

function workerstart($worker)
{
//    read param or conn database
//    global $db;
//    $db=new mysqldb(array('host'    => $worker->config['mysql_host'],
//                          'port'    => $worker->config['mysql_port'],
//                          'user'    => $worker->config['mysql_user'],
//                          'passwd'  => $worker->config['mysql_passwd'],
//                          'name'    => $worker->config['mysql_db'],
//                          'persistent' => false, //MySQL长连接
//    ));
//    $db->connect();
}
