<?php

/**
 * Swoole Http Server封装类
 * @author jiaofuyou@qq.com
 * @date   2014-11-25
 */

class swoole_http extends swoole
{
    public function _init()
    {        
        $this->serv->on('Request', array($this, 'my_onRequest'));
    }
    
    private function response($response, $status, $result, $header = array())
    {
        if ( $status <> 200 ) {
            Log::prn_log(ERROR, "$status $result");
            $response->status($status);
            $result = json_encode(array('error'=>$result, 'status'=>$status));
        }

        Log::prn_log(NOTICE, "response:");
        echo "$result\n";

        foreach($header as $key => $val) $response->header($key, $val);
        $response->end($result);    
    }

    function my_onRequest(swoole_http_request $request, swoole_http_response $response)
    {
        //var_dump($request);

        // 请求的文件        
        if ( $request->server['request_method'] <> 'POST' ) {
            return $this->response($response, 405, 'Method Not Allowed, ' . $request->server['request_method']);     
        }

        $uri = $request->server['request_uri'];
        if ( !preg_match('#^/(\w+)/(\w+)$#', $uri, $match) ) {
            return $this->response($response, 404, "'$uri' is not found!");  
        }  
        $class = $match[1].'_controller';
        $fun = $match[2];
        //判断类是否存在
        if (! class_exists($class)  || !method_exists(($class),($fun))) {
            return $this->response($response, 404, " class or fun not found class == $class fun == $fun");
        };

        $content = $request->rawContent();
        if ( $content === false ) $content = '';
        if ( $content === '' ) 
        {
            Log::prn_log(ERROR, $content);
            return $this->response($response, 415, 'post content is empty!');        
        }

        Log::prn_log(NOTICE, "request:");
        echo "api: $match[1].$match[2]\ncontent: \n$content\n";

        $obj = new $class($this->serv, $request);
        return $this->response($response, 200, $obj->$fun(), array('Content-Type' => 'application/json'));
    }
    
    public function start(){
        parent::start();
        $this->serv->start();
    }    
}
