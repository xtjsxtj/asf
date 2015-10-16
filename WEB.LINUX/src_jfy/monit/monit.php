<?php

/**
 * php 系统监控脚本
 * -- 监控116机redis，logstash:redis队列的长度
 */

$alert_mobile = '13189149999';
$alert_email  = 'jiaofuyou@qq.com';
$alert_media  = 'all'; //all,sms,mail,wqy

$interval = 60;     //监控频率

putenv("MONIT_HOST=172.16.18.114");

function send_alert()
{
    global $alert_mobile;
    global $alert_email;
    global $alert_media;
    $cmd_alert = "/usr/bin/php /usr1/app/php/send_alert.php {$alert_media} {$alert_mobile} {$alert_email}";
    exec($cmd_alert);
}

function check_redis_llen()
{
    global $alert_mobile;
    global $alert_email;
    global $alert_media;
    $service = '172.16.18.116';
    $key = 'logstash:redis';
    echo '['.date('Y-m-d H:i:s').']'."check {$service} redis ({$key}) listlen ...\n";
    $out = exec("ssh -p 2014 {$service} /usr/local/redis/bin/redis-cli llen logstash:redis");
    $llen = intval($out);    
    if ( $llen > 1000 ) {
        echo "llen=$llen, send alert ...\n";
        putenv("MONIT_EVENT=REDIS_LISTLEN_CHECK");
        putenv("MONIT_SERVICE={$service}");
        putenv("MONIT_DATE=".date('Y-m-d H:i:s'));        
        putenv("MONIT_DESCRIPTION=({$key}) listlen is abnormal, {$llen}");  
        $alert_media = 'mail';
        send_alert();
    }
    echo "\n";    
}
while(true){
    check_redis_llen();

    sleep($interval);
}
