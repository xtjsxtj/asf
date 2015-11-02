<?php

/**
 * Protocol interface
 */
interface protocol
{
    /**
     * 用于分包，即在接收的buffer中返回当前请求的长度（字节）
     * 如果可以在$recv_buffer中得到请求包的长度则返回长度
     * 否则返回0，表示需要更多的数据才能得到当前请求包的长度
     * 如果返回false或者负数，则代表请求不符合协议，则连接会断开
     * @param swoole_srver $serv swoole_server对象
     * @param int $fd TCP客户端连接的文件描述符
     * @param string $data 收到的数据内容，可能是文本或者二进制内容
     * @return int|false
     */
    public static function input($serv, $fd, $data);
    
    /**
     * 用于请求解包
     * input返回值大于0，并且收到了足够的数据，则自动调用decode
     * 然后调用request，并将decode解码后的数据传递给request的第三个参数
     * 也就是说当收到完整的客户端请求时，会自动调用decode解码，无需业务代码中手动调用
     * @param swoole_srver $serv swoole_server对象
     * @param int $fd TCP客户端连接的文件描述符
     * @param string $data 收到的完整的请求包，可能是文本或者二进制内容
     * @return mixed
     */
    public static function decode($serv, $fd, $data);
    
    /**
     * 用于请求分发处理，返回的结果传递给encode编码后返回给客户端
     * @param swoole $serv server实例  
     * @param int $fd 客户端连接fd   
     * @param mixed $data decode返回的数据包
     * @return mixed
     */
    public static function request($serv, $fd, $data);

    /**
     * 用于请求打包
     * 底层会自动把on_request返回的结果用encode打包一次，变成符合协议的数据格式
     * 也就是说发送给客户端的数据会自动encode打包，无需业务代码中手动调用
     * @param swoole_srver $serv swoole_server对象
     * @param int $fd TCP客户端连接的文件描述符
     * @param mixed $data request返回的数据包
     * @return string
     */
    public static function encode($serv, $fd, $data);
}
