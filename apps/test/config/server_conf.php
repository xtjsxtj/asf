<?php

class Swoole_conf {
    public static $config=array(
        'server_name' => 'test',
        'pid_file' => '/var/local/swoole_test.pid',
        'log_level' => DEBUG, 
        'is_sington' => true,
        'listen' => ['0.0.0.0:9501', '172.16.18.116:9502'],
        'worker_num' => 1,
        'daemonize' => 1,
        'log_file' => '/home/jfy/testprog/asf/apps/test/index.log',
    );
}
