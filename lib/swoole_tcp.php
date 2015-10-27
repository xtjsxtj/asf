<?php

/**
 * Swoole Tcp Server封装类
 * @author jiaofuyou@qq.com
 * @date   2014-11-25
 */

class swoole_tcp extends swoole
{
    public function _init()
    {
        $this->serv->on('Receive', array($this, 'my_onReceive'));
    }
    
    public function my_onReceive($serv, $fd, $from_id, $data)
    {
        //Log::prn_log(DEBUG, "WorkerReceive: client[$fd@{$serv->connection_info($fd)['remote_ip']}] : \n$data");
        $reqdata=call_user_func($this->on_func['input'], $serv, $fd, $from_id, $data);
        if ( $reqdata === false ) return;
        if ( $reqdata === -1 ) {
            $serv->close($fd);
            return;
        }
        $reqdata=call_user_func($this->on_func['request'], $serv, $fd, $from_id, $reqdata);
        
        $request = [
            'conninfo' => $this->serv->connection_info($fd),
            'fd' => $fd,
            'from_id' => $form_id,
            'server' => $this->serv,
            'content' => $reqdata['content'],  //这个字段值由input中处理，该值会再传入request中处理
            'class_name' => $reqdata['class_name'],
            'method_name' => $reqdata['method_name'],
        ];
      
        $class = $request['class_name'].'_controller';
        $fun = $request['method_name'];
        //判断类是否存在
        if (! class_exists($class)  || !method_exists(($class),($fun))) {
            return $this->response($response, 404, " class or fun not found class == $class fun == $fun");
        };

        Log::prn_log(NOTICE, "request:");
        echo "api: {$request['class_name']}.{$request['method_name']}\ncontent: \n{$request['content']}\n";
        $obj = new $class($this->serv, $request);
        $response = $obj->$fun();

        //Log::prn_log(DEBUG, "WorkerReponse: client[$fd@{$serv->connection_info($fd)['remote_ip']}] : \n$response");
        $serv->send($fd, $response);

        return;
    }
    
    public function start(){
        parent::start();
        
        //input回调函数负责将tcp分段流数据按协议长度组合成一条完整的请求包
        if ( !function_exists($this->on_func['input']) ) {
            Log::prn_log(ERROR, 'on_input is must by register!');
            exit;
        }
        
        //request回调函数负责解析TCP协议包变成内部格式，同时分配controller和method
        if ( !function_exists($this->on_func['request']) ) {
            Log::prn_log(ERROR, 'on_request is must by register!');
            exit;
        } 
        
        $this->serv->start();
    }
}
