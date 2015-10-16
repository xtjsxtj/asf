<?php

/*
pt-heartbeat -D 3alogic --update --user=root --password=cpyf -h172.16.18.114 --create-table --daemonize
pt-heartbeat -D 3alogic --monitor --user=root --password=cpyf -h172.16.18.116
pt-heartbeat -D 3alobic --check --user=root --password=cpyf -h172.16.18.187
*/

$slave_server_list = array (
    array(
        'host' => '172.16.18.116',
        'user' => 'root',
        'pass' => 'cpyf',
        'name' => '3alogic',
    ),
//    array(
//        'host' => '172.16.18.165',
//        'user' => 'root',
//        'pass' => 'cpyf',
//        'name' => '3alogic',
//    ),
    array(
        'host' => '58.64.142.44',
        'user' => 'root',
        'pass' => 'cpyf',
        'name' => '3alogic',
    ),
);

$mobile = '13189149999';
$email  = 'jiaofuyou@qq.com';
$to     = 'mail,wqy'; //all,sms,mail,wqy

$cmd_alert = "/usr/bin/php /usr1/app/php/send_alert.php {$to} {$mobile} {$email}";

while(true){
    echo '['.date('Y-m-d H:i:s').']'."slave heartbeat ...\n";

    foreach($slave_server_list as $server){
        $cmd="/usr/local/mysql/bin/pt-heartbeat -D {$server['name']} --master-server-id=1 --check --user={$server['user']} --password={$server['pass']} -h{$server['host']}";
        $out=null;
        exec($cmd, $out);
        $delay = intval($out[0]);
        echo "slave server [{$server['host']}] delay: {$delay}s\n";
        if ($delay > 120) {
            echo "send alert ...\n";
            putenv("MONIT_EVENT=mysql slave heartbeat");
            putenv("MONIT_SERVICE={$server['host']}");
            putenv("MONIT_DATE=".date('Y-m-d H:i:s'));
            putenv("MONIT_HOST=172.16.18.114");
            putenv("MONIT_DESCRIPTION=slave delay {$delay}s");
            exec($cmd_alert);
        }
        if ($delay > 600) {
            if (!isset($delay_restart[$server['host']])) $delay_restart[$server['host']] = 0;
            if (time() - $delay_restart[$server['host']] < 600) continue;
            $delay_restart[$server['host']] = time();
            echo "slave server [{$server['host']}] delay too long, slave stop and start\n";
            $cmd="/usr/local/mysql/bin/mysql -h {$server['host']} -e 'STOP SLAVE IO_THREAD; START SLAVE IO_THREAD;'";
            $out=null;
            exec($cmd, $out);
        }
    }
    echo "\n";

    sleep(60);
}
