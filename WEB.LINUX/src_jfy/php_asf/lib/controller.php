<?php

class controller { 
    protected $server;
    protected $request;
    protected $content;
    
    public function __construct($server, $request) {
        $this->server = $server;
        $this->request = $request;
        $this->content = $request->rawContent();
        
        if (method_exists($this, '_init')) $this->_init();
    }
    
    public function __destruct() {
        if (method_exists($this, '_deinit')) $this->_deinit ();
    }
}
