<?php

class Worker_conf{
    public static $config=array(
        'log_level' => DEBUG,
        'server' => array(
            'mysql_host' => '127.0.0.1',
            'mysql_port' => 3306,
            'mysql_user' => 'root',
            'mysql_passwd' => 'cpyf',
            'mysql_db' => 'test',
        )
    );
    
    public static $route_config = [
        //_handler.* 控制器为底层通用控制器，会根据具体的controller/action进行再次分发
        
        ['PUT', '/user/number/{id:\d+}',              'index.index'],
        ['GET', '/{controller}/{number}/{id:\d+}',    '_handler.controller_param'],
        ['GET', '/{controller}/{number}',             '_handler.controller_param'],
        ['POST', '/{controller}/{number}/{id:\d+}',   '_handler.controller_param'],
        ['DELETE', '/{controller}/{number}/{id:\d+}', '_handler.controller_param'],
        
        //下面这三条规则，由底层填加到算定义规则的后面
        //['POST', '/{controller}/{action}[/]',        '_handler.controller_action'],
        //['POST', '/{controller}[/]',                 '_handler.controller'],         
        //[['GET','POST'], '/{controller}/{param:.+}', '_handler.controller_param'],
    ];     
}
