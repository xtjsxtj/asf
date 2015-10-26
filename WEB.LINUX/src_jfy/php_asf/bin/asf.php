<?php

/**
 * App Server Framework 启动脚本
 * @author jiaofuyou@qq.com
 * @date   2015-11-25
 */

error_reporting(E_ALL);

if(empty($argv[1]))
{
    echo "Usage: asf <server_name> <start|stop|restart|reload|status|init>".PHP_EOL;
    exit;
}

$serv = $argv[1];
$cmd = $argv[2];
$server_file = __DIR__ . "/../apps/$serv/$serv.php";
$pid_file = '/var/local/swoole_' . substr(basename($server_file), 0, -4) . ".pid";

switch($cmd)
{
    case 'start':
        if (file_exists($pid_file)){
            $pid = file_get_contents($pid_file);
            $pid = intval($pid);
            if ($pid > 0 && posix_kill($pid, 0)){
                exit("the server is already started!\n");
            }
        }
        start_and_wait(15);
        exit;
        break;
    case 'stop':
        stop_and_wait(5);
        exit;
        break;
    case 'restart':
        stop_and_wait(5);
        start_and_wait(15);
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
        exec("ps -ef | grep $serv | grep -v grep | grep -v asf", $ret);
        foreach($ret as $line) echo $line."\n";
        break;
    case 'init':
        app_init();
        break;        
    default:
        echo "Usage: asf <server_name> <start|stop|restart|reload|status|init>".PHP_EOL;
        exit;

}

function app_init()
{
    global $serv;
    $app_examples_test_path = __DIR__.'/../apps/test';
    $app_new_path = __DIR__ . "/../apps/$serv";
    
    if ( file_exists($app_new_path) ) {
        echo "new app [$app_new_path] is exists\n";
        exit;
    }
        
    echo exec('/bin/cp -r ' . $app_examples_test_path .' '. $app_new_path);
    echo exec('/bin/mv ' . $app_examples_test_path.'/test.php ' . $app_new_path.'/'.$serv.'.php');
        
    echo "app [$serv] init ok\n";
}

function start_and_wait($wait_time = 5)
{
    global $server_file;
    global $pid_file;
    global $serv;

    echo exec("/usr/bin/php $server_file");

    $start_time = time();
    $succ=false;
    while(true)
    {
        if (file_exists($pid_file)){
            $pid = file_get_contents($pid_file);
            $pid = intval($pid);
            if ($pid > 0 && posix_kill($pid, 0)){
                exec("ps -ef | grep $serv | grep -v grep", $ret);
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
    $succ = true;
    if ( $succ )
        echo "Server start ok!\n";
    else
        echo "Server start error, please view logfile!\n";

    return;
}

function stop_and_wait($wait_time = 5)
{
    global $pid_file;

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
            break;
        }
    }

    echo "Server stop ok!\n";

    return;
}
