<?php

class Swoole_conf {
    public static $config=array(
        'log_level' => DEBUG, 
        'is_sington' => true,
        'swoole_host' => '0.0.0.0',
        'swoole_port' => array('9501'=>'tcp','8848'=>'http'),
        'worker_num' => 12,
        //'ipc_mode' => 2,
        'daemonize' => 1,
        'log_file' => '/usr1/app/logs/voip_server.log',
    );
}
