<?php

require_once dirname(__FILE__).'/./voip.php';
require_once dirname(__FILE__).'/./swoole.php';

function prco_http_request($server, $fd, $recv_buffer)
{
    global $worker;

    http_start($recv_buffer);
//    var_dump($_POST);
//    var_dump($_GET);
//    var_dump($_REQUEST);

    // 请求的文件
    $url_info = parse_url($_SERVER['REQUEST_URI']);
    if(!$url_info)
    {
        prn_log('400 Bad Request');
        return http_end(array('HTTP/1.0 400 Bad Request'), '400 Bad Request');
    }
    $path_info = pathinfo($url_info['path']);
    $path_api = $path_info['filename'];

    if ( count($_REQUEST) == 0 ) {
        prn_log("query is not defined!");
        $server-send($fd, http_end(array('HTTP/1.0 400 Bad Request'), "query is not defined!"));
        return;
    }
    if ($_SERVER['REQUEST_METHOD']=='GET') {
        $query = $url_info['query'];
    } else {
        $query = $GLOBALS['HTTP_RAW_POST_DATA'];
    }

    prn_log('http_request > '."$path_api:$query");
    if (function_exists($path_api)) {
        $reply = $path_api($query);
        prn_log('http_reponse < '."$path_api:$reply");
        $server-send($fd, http_end(array('HTTP/1.0 200 OK'), $reply));
    } else
    if ( count($worker[$path_api]) > 0 ) {
        $reply = swoole_rpc_call($server, $fd, $query, $path_api);
    } else {
        prn_log('http_reponse < '."api:$path_api is not defined!");
        $server-send($fd, http_end(array('HTTP/1.0 400 Bad Request'), "api:$path_api is not defined!"));
    }
}

function proc_tcp_request($server, $fd, $recv_buffer)
{
    prn_log(__FUNCTION__.'>'.$recv_buffer);
    $server->send('OK');

    return;
}

function proc_swoole_request($server, $fd, $recv_buffer)
{
    prn_log('swoole_request > '.$recv_buffer);

    $req=json_decode($recv_buffer, true);
    if ( $req===NULL ) {
        prn_log('param is error!');
        $server->send($fd, assign_result('MMM', 'param is error!'));
        return;
    }
    if ( !isset($req['cmd']) ) {
        prn_log('param is error!');
        $server->send($fd, assign_result('MMM', 'param is error!'));
        return;
    }
    if (!function_exists($req['cmd'])) {
        prn_log("cmd:{$req['cmd']} is not defined!");
        $server->send($fd, assign_result('MMM', "cmd:{$req['cmd']} is not defined!"));
        return;
    }
    if ( !isset($req['prm']) ) $req['prm']=NULL;

    $req['cmd']($server, $fd, $req);

    return;
}


