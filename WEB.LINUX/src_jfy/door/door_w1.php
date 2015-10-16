<?php

/**
 * Door Gearman Worker
 * @author jiaofuyou@qq.com
 * @date 2014-11-25
 */

//error_reporting(E_ALL & ~E_WARNING);   //闭关警告提示

require_once dirname(__FILE__).'/../lib/worker.php';
require_once dirname(__FILE__).'/../lib/log.php';
require_once dirname(__FILE__).'/../lib/mysql.php';
require_once dirname(__FILE__).'/../lib/func.php';
require_once dirname(__FILE__).'/./pub.php';

$worker=new Worker(
    array(
        'is_sington' => false,
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
        'door.test' => 'test',
    )
);
$worker->start();

/**
 * 测试
 */
function test($job)
{
    global $db;

    $request=$job->workload();
    $funcname=$job->functionName();
    Log::prn_log(NOTICE, 'request:'.$funcname.':'.$request);

    $req=decode_json($request);
    if ( $req == NULL ) {
        Log::prn_log(ERROR, 'param error, json_decode is NULL!');
        return assign_result('MMM', 'param error!');
    }

    //this do ...
    $sqlstr= 'insert ...';
    if ( !$db->insert_one($sqlstr) ) return assign_result('MMM', 'insert error!');

    $result = array("111","教富有");
    $result = assign_result('000', $result);
    Log::prn_log(NOTICE, 'result:'.$result);

    return $result;
}
