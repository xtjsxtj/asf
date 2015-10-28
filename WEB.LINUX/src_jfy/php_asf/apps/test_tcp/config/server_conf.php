<?php

class Swoole_conf {
    public static $config=array(
        'server_name' => 'test_tcp',
        'server_type' => 'tcp',
        'log_level' => DEBUG, 
        'is_sington' => true,
        'listen' => ['0.0.0.0:9511', '172.16.18.116:9512'],
        'worker_num' => 1,
        'daemonize' => 0,
        'log_file' => '/home/jfy/testprog/asf/apps/test_tcp/index.log',
    );   
}
