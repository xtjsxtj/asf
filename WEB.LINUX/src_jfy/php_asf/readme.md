App Server Framework（ASF）
==========================

**简价**
--------

- 构架基于PHP-Swoole扩展开发，通过配置文件支持HTTP和TCP两种Server。
- 框架本身是一个server，不再需要apache,nginx,fpm这些，框架已包含log处理，mysql访问封装。
- 框架用fast-route库来做http route处理，直接映射到控制器上,使用者只要写具体的控制器方法就可以实现rest风格的API。
- 至于性能，可以很低调的说：相当高，具体可以参考swoole相关文档：
http://www.swoole.com/

**安装运行**
-----------
```
tar zxvf asf.tar.gz  
cd asf  
php ./bin/asf.php test_http start  

也可以直接进入具体server目录直接运行入口脚本文件：  
cd asf/apps/test_http
php ./index.php

查看当前server进程状态：
php asf/bin/asf.php test_http status

查看所有server运行状态：
php asf/bin/asf.php list
```

**目录结构**
-----------
```
ASF
    apps                             #示例server或实际应用server（实际应用不限制一定放在该目录）
        test_http                    #具体应用server示例，http_server
            config                   #应用配置文件目录
                server_conf.php      #server控制进程配置文件
                worker_conf.php      #worker处理进程配置文件
            controller               #应用控制器目录
                base_controller.php  #业务相关控制器的基类
                index_controller.php #业务相关具体控制器类
            index.php                #应用入口主文件(不限于该名称)，可以单独调用，可以通过bin/asf.php说统一调用
    bin
        asf.php                      #多server起动状态监控shell脚本
        asf.ini                      #多server列表配置文件
    lib                              #ASF底层代码
        fast-route                   #fast-route库目录
        autoload.php                 #自动加载脚本
        config.php                   #route配置脚本
        controller.php               #控制器父类
        log.php                      #日志类
        mysql.php                    #mysql访问类
        route.php                    #http route解析类
        swoole.php                   #swoole扩展底层类        
```

**配置**
-------
* ASF框架程序配置分为两部分，一个是系统进程的配置server_conf，不能动态修改。
* 另一个是工作进程的配置worker_conf，可以动态修改，修改后通过asf.php server_name reload生效。  
* 下面分开详细介绍。

**server_conf.php配置文件详解**
-----------------------------

* server_name  
sever名称，必须配置，当起动多个server时，保证每个server_name的唯一。

* server_type  
server类型，必须配置，目前支持http和tcp两种server类型。

* log_level  
跟踪级别，必须配置，TRACE,DEBUG,INFO,NOTICE,WARNING,ERROR。

* is_sington  
是否单例运行，可选配置，默认为true，即单实例运行。  
假如server_name为test_http，那么server启动后默认会在/var/local目录下生成一个唯一的pid文件：swoole_test_http.pid。

* listen
监听端口，必须配置，可以是如下几种配置方式：  
```
'listen' => 9501
'listen' => [9501,9502]
'listen' => ['0.0.0.0:9501', '172.16.18.116:9502']
```

* worker_num
工作进程数量，可选配置，默认为6个工作进程

* daemonize
是否以守护进程方式运行，可选配置，默认为true，即以后台方式运行。

* log_file
跟踪文件，必须配置。

* 最简配置

    ```php
    $config=array(
        'server_name' => 'test_http',  //server_name
        'log_level' => NOTICE,         //log_level
        'listen' => 9501,              //listen port
        'log_file' => '/asf/apps/test_http/index.log',  //logfile
    );
    ```


**worker_conf.php配置文件详解**
-----------------------------
该配置文件分成三个部分。
* log_level  
工作进程的跟踪级别，取值同server_conf中相同，reload后重新生效。

* myql  
数据库连接属性配置
```
'host'       => 'localhost',  //mysql主机
'port'       => 3306,         //mysql端口
'user'       => 'user',       //mysql用户，必须参数
'password'   => 'password',   //mysql密码，必须参数
'database'   => 'database',   //数据库名称，必须参数
'charset'    => 'utf8',       //连接数据库字符集
'sqls'       => 'set wait_timeout=24*60*60*31;set wait_timeout=24*60*60*31'  
                              //连接数据库后需要执行的SQL语句,以';'分隔的多条语句
```

* route  
底层根据这里配置的跌幅规则，将http不同的uri请求分配给相应的控制器处理。
具体规则下面详细说明。

    ```php
    $config=array(
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
    ```


**路由规则配置详细说明**
----------------------
* 配置格式
    每一条配置规则为一数组类型：    
    ```  
    ['Method', 'Route_reg', 'Controller.Action']  
    
    Method            POST，GET，PUT，DELETE等  
    Route_reg         路由规则的正则表达式，用以匹配uri路径  
    Controller.Action 分配到的处理请求的控制器
    ```

* 控制器  
底层提供一个默认的控制器_handler，该控制器提供四个方法：
    * controller_action         解析'/index/index' uri到相应的控制器的相应方法
    * controller                解析'/index' uri到相应的控制器的默认index方法
    * controller_param          解析'/index/name/id' uri到相应的控制器的默认index方法,同时附加param参数
    * controller_action_param   解析'/index/index/name/id' uri到相应的控制器的相应方法,同时附加param参数
    
   
* 用户自定义规则示例
```
['PUT', '/user/number/{id:\d+}',              'index.index'],
['PUT', '/user/{number}/{id:\d+}',            'index.index'],
```

    第1条规则可以匹配以下规则：
    PUT http://localhost/user/number，分配到index控制器的index方法，同时控制器的$this->param参数中保存着：{"id":"123"}
    
    第2条规则可以匹配以下规则：
    PUT http://localhost/user/number，分配到index控制器的index方法，同时控制器的$this->param参数中保存着：{"number":"number","id":"123"}

* 默认规则  
底层会在用户自定义的路由规则后面增加三条默认的规则：
```
['POST', '/{controller}/{action}[/]',        '_handler.controller_action'],
['POST', '/{controller}[/]',                 '_handler.controller'],         
[['GET','POST'], '/{controller}/{param:.+}', '_handler.controller_param'],
```

    第1条规则可以匹配以下规则：  
    POST http://localhost/index/test，分配到index控制器的test方法。 
       
    第2条规则可以匹配以下规则：  
    POST http://localhost/index，分配到index控制器的默认index方法。
    
    第3条规则可以匹配以下规则：  
    POST http://localhost/index/test/name/id，分配到index控制器的默认index方法,同时控制器的$this->param参数中保存着：test\name\id

* 规则匹配顺序  
系统按照先自定义规则，再默认规则的顺序执行，从上往下依次匹配，匹配到了就返回。
因此，定义规则时应该保证，具体的匹配规则在上面，通用的在下面。

**系统起动脚本使用**
------------------

* asf.ini 文件说明  

    ```ini
    [servers]
    test_http = /home/jfy/testprog/asf/apps/test_http/index.php
    test_tcp = /home/jfy/testprog/asf/apps/test_tcp/index.php
    ```
    每一行格式为: server_name = path/index.php
    这里的server_name必须与每个应用中的server_conf配置文件中的server_name相同。
    
* asf.php 脚本使用
    ```
    php asf.php list
    ```
    列出asf.ini文件中配置的所有的server_name对应的服务的运行状态
    
    ```
    php asf.php server_name start|stop|reload|restart|status|init
    ```
    reload：     平时重启工作进程，并重新加载worker_conf配置文件内容  
    restart：    重启整个server
    status：     显示server进程状态
    init：       根据test_http框架复制一份进应用目录框架
    
    server_name必须是asf.ini文件中已经定义好的。
    
**控制器父类属性方法**
--------------------

* 属性  

    ```
    protected $server;    //swoole_server对象
    protected $mysql;     //数据访问对象
    protected $request;   //swoole_http_request
    protected $content;   //POST提交的body
    protected $param;     //handle_route解析路由后附加参数
    ```

* 方法    
__construct时，如果子类中存在_init则会被执行。  
__destruct时，如果子类中存在_deinit则会被执行。

**log类使用方法**
----------------

* log类以静态类方式使用。

* 设置跟踪级别：  
log::log_level = NOTICE;  
支持：TRACE,DEBUG,INFO,NOTICE,WARNING,ERROR

* 打印跟踪  
log::prn_log(NOTICE, "message");
支持：TRACE,DEBUG,INFO,NOTICE,WARNING,ERROR

**mysql类使用方法**
------------------
* 直接在控制器用$this->mysql来操作mysql数据库。
* select_one($sqlstr,$flag=true)

    ```
    /**
     * 查询唯一记录
     * @param string $sql 执行的SQL语句
     * @flag bool 查询不到或查询到多条是否打印ERROR log
     * @return row(array) | false
     */
    ```

* select_more($sqlstr)

    ```
    /**
     * 查询多条记录
     * @param string $sql 执行的SQL语句
     * @return result(array) | false
     */
    ```

* insert($data,$flag=true)

    ```
    /**
     * 插入单条记录
     * @param array $data 插入数据字段数组['id' => 123, 'name' => 'jfy']
     * @param bool $flag 是否打印成功跟踪，默认为true
     * @return id | false 成功返回自增字段ID，失败返回false
     * @see mysql->tabname->insert(['id' => 123, 'name' => 'jfy']);
     */
    ```

* insert_one($sqlstr,$flag=true)

    ```
    /**
     * 插入单条记录
     * @param string $sql 执行的SQL语句
     * @return true | false
     * $this->insert_id 为自增字段ID
     */
    ```

* update($data,$cond)

    ```
    /**
     * 更新单条记录
     * @param array $data 更新数据字段数组['name' => 'jfy']
     * @param array $cond 更新条件字段数组['id' => 123]，顺序与索引顺序相同
     * @return true | false
     * @see $this->affected_rows 为更新记录数
     * @see mysql->tabname->update(['name' => 'jfy'],['id' => 123]);
     */
    ```

* update_one($sqlstr)

    ```
    /**
     * 更新单条记录
     * @param string $sql 执行的SQL语句
     * @return true | false
     * @see $this->affected_rows 为更新记录数
     */
    ```

* update_more($sqlstr)

    ```
    /**
     * 更新多条记录
     * @param string $sql 执行的SQL语句
     * @return true | false
     * @see $this->affected_rows 为更新记录数
     */
    ```

* delete($cond)

    ```
    /**
     * 删除单条记录
     * @param array $cond更新条件字段数组['id' => 123]，顺序与索引顺序相同
     * @return true | false
     * @see $this->affected_rows 为删除记录数
     * @see mysql->tabname->delete(['id' => 123]);
     */
    ```

* delete_one($sqlstr)

    ```
    /**
     * 删除单条记录
     * @param string $sql 执行的SQL语句
     * @return true | false
     * @see $this->affected_rows 为删除记录数
     */
    ```

**控制器示例**
-------------
```php
<?php

class index_controller extends base_controller {       
    public function index() {
        log::prn_log(DEBUG, json_encode($this->param));
        
        $db = $this->mysql;
        
        $result = $db->gearman_queue->insert([
            'unique_key' => 'd847233c-1ef2-11e5-9130-2c44fd7aee72',
            'function_name' => 'test',
            'priority' => 1,
            'data' => 'test',
            'when_to_run' => 0,
        ]);
        if ( $result === false ) return 'error';
        
        $result = $db->select_one("select * from gearman_queue where unique_key='d847233c-1ef2-11e5-9130-2c44fd7aee72'");
        if ( $result === false ) return 'error';
        var_dump($result);
        
        $result = $db->gearman_queue->update([
            'function_name' => 'testtest',
            'priority' => 100,
            'data' => 'testtesttesttest',
            'when_to_run' => 100,
        ],[
            'unique_key' => 'd847233c-1ef2-11e5-9130-2c44fd7aee72',
        ]);
        if ( $result === false ) return 'error';        
        var_dump($db->select_one("select * from gearman_queue where unique_key='d847233c-1ef2-11e5-9130-2c44fd7aee72'"));
        
        $result = $db->gearman_queue->delete([
            'unique_key' => 'd847233c-1ef2-11e5-9130-2c44fd7aee72',
        ]);         
        if ( $result === false ) return 'error';
        
        $result = $db->select_more("select * from gearman_queue limti 3");
        if ( $result === false ) return 'error';
        var_dump($result);
        
        return 'ok';
    }
}
```

在完全默认的配置下：
http://localhost:9501/index/index  路由将会执行上面index方法。
