<?php

/**
 * App Server Framework 启动脚本
 * @author jiaofuyou@qq.com
 * @date   2015-11-25
 * 
 * php asf.php servername start|stop|reload|restart|status|init
 * php asf.php list
 * 
 */

error_reporting(E_ALL);

require_once __DIR__.'/../lib/autoload.php';  

if(empty($argv[1]))
{
    print_info();
    exit;
}

$servers = parse_ini_file(__DIR__.'/asf.ini',true)['servers'];

$param['server_name'] = $argv[1];
$param['servers'] = $servers;

if ( $param['server_name'] === 'list' ) {
    list_server($param);
    exit;
}
    
if ($argc <= 2){
    print_info();
    exit;
}
$param['cmd'] = $argv[2];

if ( !isset($servers[$param['server_name']]) ) {
    echo "server name [{$param['server_name']}] is not exists\n";
    exit;
}
$param['server_file'] = $servers[$param['server_name']];
$param['server_path'] = dirname($param['server_file']);

if (  $param['cmd'] === 'init' ) {
        $param['server_examples_test_path'] = dirname($servers['test_http']);
        app_init($param);
        exit;
}

$param['pid_file'] = swoole::$info_dir . "swoole_{$param['server_name']}.pid";
$pid_file = $param['pid_file'];

switch( $param['cmd'] )
{    
    case 'start':
        if (file_exists($pid_file)){
            $pid = file_get_contents($pid_file);
            $pid = intval($pid);
            if ($pid > 0 && posix_kill($pid, 0)){
                exit("the server is already started!\n");
            }
        }
        start_and_wait($param, 15);
        exit;
        break;
    case 'stop':
        stop_and_wait($param, 5);
        exit;
        break;
    case 'restart':
        stop_and_wait($param, 5);
        start_and_wait($param,15);
        exit;
        break;
    case 'reload':
        $pid = @file_get_contents($pid_file);
        if(empty($pid))
        {
            exit("Server is not running!\n");
        }
        if (!posix_kill($pid, 0)){
            exit("Server is not running!\n");
        }
        posix_kill($pid, SIGUSR1);
        echo "Server reload ok!\n";
        break;
    case 'status':
        $pid = @file_get_contents($pid_file);
        if(empty($pid))
        {
            exit("Server is not running!\n");
        }
        if (!posix_kill($pid, 0)){
            exit("Server is not running!\n");
        }
        exec("ps -ef | grep {$param['server_name']} | grep -v grep | grep -v asf", $ret);
        foreach($ret as $line) echo $line."\n";
        break;      
    default:
        echo "Usage: asf <server_name> <start|stop|restart|reload|status|init>".PHP_EOL;
        exit;

}

function list_server($param){
    foreach($param['servers'] as $server_name => $server_file) {
        $pid_file = swoole::$info_dir . "swoole_{$server_name}.pid"; 
        $pid = @file_get_contents($pid_file);
        if(empty($pid))
        {
            echo sprintf('%-16s ', "[$server_name]")."Server is not running!\n";
            continue;
        }
        if (!posix_kill($pid, 0)){
            echo sprintf('%-16s ', "[$server_name]")."Server is not running!\n";
            continue;
        } 
        echo sprintf('%-16s ', "[$server_name]")."Server is running!\n";
    }
}

function app_init($param)
{  
    if ( file_exists($param['server_path']) ) {
        echo "new app [{$param['server_path']}] is exists\n";
        exit;
    }
        
    echo exec('/bin/cp -r ' . $param['server_examples_test_path'] .' '. $param['server_path']);
    
    $config_file = "{$param['server_path']}//config//server_conf.php";
    $content = file_get_contents($config_file);
    $content = str_replace("'server_name' => 'test'","'server_name' => '{$param['server_name']}'",$content);
    file_put_contents($config_file,$content);
   
        
    echo "app [{$param['server_name']}] init ok\n";
}

function start_and_wait($param, $wait_time = 5)
{
    global $param;
    $pid_file = $param['pid_file'];
    $server_file = $param['server_file'];
    $server_name = $param['server_name'];

    echo exec("/usr/bin/php $server_file");

    $start_time = time();
    $succ=false;
    while(true)
    {
        if (file_exists($pid_file)){
            $pid = file_get_contents($pid_file);
            $pid = intval($pid);
            if ($pid > 0 && posix_kill($pid, 0)){
                exec("ps -ef | grep $server_name | grep -v grep", $ret);
                if ( count($ret) > 2 ) {
                    $succ=true;
                    break;
                }
            }
        }
        clearstatcache();
        usleep(100);
        if(time()-$start_time >= $wait_time)
        {
            usleep(500000);
            break;
        }
    }
    if ( $succ )
        echo "Server start ok!\n";
    else
        echo "Server start error, please view logfile!\n";

    return;
}

function stop_and_wait($param, $wait_time = 5)
{
    global $param;
    $pid_file = $param['pid_file'];
    $server_file = $param['server_file'];
    $server_name = $param['server_name'];

    $pid = @file_get_contents($pid_file);
    if(empty($pid))
    {
        exit("Server is not running!\n");
    }
    if (!posix_kill($pid, 0)){
        exit("Server is not running!\n");
    }
    posix_kill($pid, SIGTERM);

    $start_time = time();
    while(is_file($pid_file))
    {
        clearstatcache();
        usleep(1000);
        if(time()-$start_time >= $wait_time)
        {
            posix_kill($pid, SIGTERM);
            posix_kill($pid, SIGTERM);
            unlink($pid_file);
            usleep(500000);
            echo aaa;
            break;
        }
    }

    echo "Server stop ok!\n";

    return;
}

function print_info(){
    echo PHP_EOL;
    echo "welcome to use App-Server-Framework:".PHP_EOL.PHP_EOL;
    echo "  php asf.php servername start|stop|reload|restart|status|init".PHP_EOL; 
    echo "  php asf.php list".PHP_EOL.PHP_EOL;
    exit;
}
