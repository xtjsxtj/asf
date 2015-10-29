<?php

class Swoole_conf {
    public static $config=array(
        'server_name' => 'test_http',
        'server_type' => 'http',   //没有该选项则默认为http server
        'log_level' => NOTICE, 
        'is_sington' => true,
        'listen' => ['0.0.0.0:9501', '172.16.18.116:9502'],
        'worker_num' => 1,
        'daemonize' => 0,
        'log_file' => '/home/jfy/testprog/asf/apps/test_http/index.log',
    );   
}
