<?php

class worker_conf{
    public static $config=array(
        'log_level' => DEBUG,
        'mysql' => array(
            'socket' => '/tmp/mysql.sock',
            'host' => 'localhost',
            'port' => 3306,            
            'user' => 'root',
            'password' => 'cpyf',
            'database' => 'test',
            'charset' => 'utf8',
        )
    );
}
