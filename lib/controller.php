<?php

/**
 * http请求处理控制器底层父类
 * @author jiaofuyou@qq.com
 * @date   2015-10-25
 */

class controller { 
    protected $server;    //swoole_server对象
    protected $mysql;     //数据访问对象
    protected $request;   //解析后的数据包
    protected $param;     //附加参数

    /**
     * @param swoole $serv swoole实例
     * @param mixed request
     * @param array $param 附加参数
     */    
    public function __construct($server, $request, $param=[]) {
        $this->server = $server;
        $this->mysql = $server->mysql;
        $this->request = $request;        
        $this->param = $param;
        
        if (method_exists($this, '_init')) $this->_init();
    }
    
    public function __destruct() {
        if (method_exists($this, '_deinit')) $this->_deinit ();
    }
}
