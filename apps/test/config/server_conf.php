<?php

class Swoole_conf {
    public static $config=array(
        'server_name' => 'test',
        'server_type' => 'http',
        'log_level' => DEBUG, 
        'is_sington' => true,
        'listen' => ['0.0.0.0:9501', '172.16.18.116:9502'],
        'worker_num' => 1,
        'daemonize' => 0,
        'log_file' => '/home/jfy/testprog/asf/apps/test/index.log',
    );
    public static $route_config = [
        ['PUT', '/{controller}/{action}/{id:\d+}',    'controller_action_param'],
        ['GET', '/{controller}/{number}/{id:\d+}',    'controller_param'],
        ['GET', '/{controller}/{number}',             'controller_param'],
        ['POST', '/{controller}/{number}/{id:\d+}',   'controller_param'],
        ['DELETE', '/{controller}/{number}/{id:\d+}', 'controller_param'],
        
        //下面这三条规则，由底层填加到算定义规则的后面
        //['POST', '/{controller}/{action}[/]',        'controller_action'],
        //['POST', '/{controller}[/]',                 'controller'],         
        //[['GET','POST'], '/{controller}/{param:.+}', 'controller_param'],
    ];    
}
