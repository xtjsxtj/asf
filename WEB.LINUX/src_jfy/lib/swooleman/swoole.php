<?php

//{"cmd": "swoole_worker_add_function", "prm": "test2"}
function swoole_worker_add_function($server, $fd, $req)
{
    global $worker;

    $prm=$req['prm'];
    $worker[$prm][$fd]=0;

    $result=assign_result('000', "funcname {$prm} add success!");
    prn_log('swoole_reponse < '.$result);

    $server->send($fd, $result);

    return;
}

function swoole_rpc_call($server, $fd, $prm, $func)
{
    global $worker;

    asort($worker[$func]);
    $tofd=key($worker[$func]);

    $request=array(
        'cmd' => $func,
        'respfd' => $fd,
        'protocol' => 'http',
        'prm' => $prm,
    );

    $result=json_encode($request);
    prn_log('http_transer < '.$result);

    $server->send($tofd, json_encode($request));
    $worker[$func][$tofd]++;

    return;
}

function swoole_respone($protocol, $param)
{
    if ( $protocol === 'http' ) return(http_end(array('HTTP/1.0 200 OK'), $param));

    return $param;
}

//{"cmd":"swoole_worker_respone","respfd":13,"protocol":"http","prm":"jfy"}
function swoole_worker_respone($server, $fd, $req)
{
    $resp_fd=$req['respfd'];
    $resp_pt=$req['protocol'];

    $result=swoole_respone($resp_pt, $req['prm']);
    prn_log('swoole_respone < '.$result);

    $server->send($resp_fd, $result);

    return;
}


