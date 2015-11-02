<?php

class Swoole_conf {
    public static $config=array(
        'server_name' => 'test_http',  //server名称
        'protocol' => 'http',          //没有该选项则默认为http server
        'log_level' => NOTICE,         //跟踪级别TRACE,DEBUG,INFO,NOTICE,WARNING,ERROR
        'is_sington' => true,          //是否单实例
        'listen' => ['0.0.0.0:9501', '172.16.18.116:9502'],  //listen监听端口
        'worker_num' => 1,             //工作进程数
        'daemonize' => false,          //是否以守护进程方式运行
        'log_file' => '/home/jfy/testprog/asf/apps/test_http/index.log',  //log文件
    );   
}
