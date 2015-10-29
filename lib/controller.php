<?php

/**
 * http请求处理控制器底层父类
 * @author jiaofuyou@qq.com
 * @date   2015-10-25
 */

class controller { 
    protected $server;    
    protected $mysql;
    protected $request;
    protected $content;
    
    public function __construct($server, $request, $param) {
        $this->server = $server;
        $this->mysql = $server->mysql;
        $this->request = $request;
        $this->content = $request->rawContent();
        $this->param = $param;
        
        if (method_exists($this, '_init')) $this->_init();
    }
    
    public function __destruct() {
        if (method_exists($this, '_deinit')) $this->_deinit ();
    }
}
