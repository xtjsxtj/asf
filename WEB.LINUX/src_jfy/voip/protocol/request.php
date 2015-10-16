<?php

function proc_input($serv, $fd, $data, $protocol)
{
    if ( $protocol=='tcp' ) return tcp_input($serv, $fd, $data);
    if ( $protocol=='http' ) {
        $conninfo=$serv->connection_info($fd);
        Log::prn_log(DEBUG, "WorkerReceive: client[$fd@{$conninfo['remote_ip']}] : \n$data");
        return http_input($fd, $data);
    }

    return $data;
}

function tcp_input($serv, $fd, $tcp_string)
{
    global $req_string;

    $conninfo=$serv->connection_info($fd);
    Log::prn_log(DEBUG, "WorkerReceive: client[$fd@{$conninfo['remote_ip']}] : \n$tcp_string");
    $tcp_string=str_replace("\r\n", "", $tcp_string);

    if ( !isset($req_string[$fd]) ) $req_string[$fd]='';
    $req_string[$fd] .= $tcp_string;
    $tcp_string=$req_string[$fd];

    if ( strlen($tcp_string) < 4 ) return false;
    $len=intval(substr($tcp_string,0,4));
    if ( strlen($tcp_string) < $len ) return false;
    if ( $len != strlen($tcp_string) ) {
        Log::prn_log(ERROR, "data length error <{$tcp_string}>!");
        $req_string[$fd] = '';
        return -1;
    }
    $req_string[$fd] = '';

    return $tcp_string;
}

function proc_request($serv, $fd, $from_id, $data, $protocol, $funclist)
{
    Log::prn_log(NOTICE, str_repeat('=', 44).' >>>>>>>> '.str_repeat('=', 44));
    if ( $protocol == 'tcp' ) {
        $result = voip_request($data,$funclist);
    } else
    if ( $protocol == 'http' ) {
        Log::prn_log(NONE, "http_request: \n$data");
        $result = http_request($serv, $fd, $data, $funclist);
        Log::prn_log(NONE, "http_reponse: \n$result");
    } else {
        $result = $data;
    }

    Log::prn_log(NOTICE, str_repeat('=', 44).' <<<<<<<< '.str_repeat('=', 44));

    return $result;
}

/*
telnet 172.16.18.114 6084

POST /get_user HTTP/1.1
Host: 172.16.18.114:6084
Content-type: text/plain
Content-length: 21

{"userid":"91085828"}
*/
function http_request($server, $fd, $recv_buffer, $funclist)
{
    global $worker;

    http_start($recv_buffer);
//    var_dump($_SERVER);
//    var_dump($_POST);
//    var_dump($_GET);
//    var_dump($_REQUEST);

    // 请求的文件
    $url_info = parse_url($_SERVER['REQUEST_URI']);
    if(!$url_info)
    {
        Log::prn_log(ERROR, '400 Bad Request');
        return http_end(array('HTTP/1.0 400 Bad Request'), '400 Bad Request');
    }

    $path_info = pathinfo($url_info['path']);
    $path_api = $path_info['filename'];

    if ( count($_REQUEST) == 0 ) {
        Log::prn_log(ERROR, "query is not defined!");
        return http_end(array('HTTP/1.0 400 Bad Request'), "query is not defined!");
    }
    if ($_SERVER['REQUEST_METHOD']=='GET') {
        $query = $url_info['query'];
    } else {
        $query = $GLOBALS['HTTP_RAW_POST_DATA'];
    }
    $query = urldecode($query);

    $funcname=$path_api;
    if ( !function_exists($funclist[$funcname]) ) {
        Log::prn_log(ERROR, "func:{$funcname} is not defined!");
        return "func:{$funcname} is not defined!";
    }

    $job=new job($funcname, $query);
    return http_end(array('HTTP/1.0 200 OK'), $funclist[$funcname]($job));
}

function voip_request($data, $funclist)
{
    Log::prn_log(NOTICE, "voip_request: $data");

    list($req['len'],$req['id'],$req['cmd'],$tmp)=explode('~', $data,4);
    if ( $req['cmd'] == 'JQ' ) {
        //0026~0~JQ~85263007733~1606
        list($reqreq['msisdn'],$reqreq['precode'])=explode('~', $tmp);
        $reqreq['msisdn']=format_msisdn($reqreq['msisdn']);
        $funcname='voip_jq';
        $job=new job($funcname, json_encode($reqreq));
        $rep=$funclist[$funcname]($job);
        $rep=json_decode(iconv("gbk", "UTF-8", $rep), true);
        $rep=gbk_iconv($rep);
        $status=intval(substr($rep['error_code'],1));
        if ($status != 0)
            $tmp=$req['id'].'~'.$req['cmd'].'~'.$status.'~'.$rep['error_msg'];
        else
            $tmp=$req['id'].'~'.$req['cmd'].'~'.$status.'~'.$rep['rate_code'];
    } else
    if ( $req['cmd'] == 'KF' ) {
        //0083~0~KF~85263007733~callnoa~callnob~0.01~60~85238029619_MOC_20141117-122222_001~1
        list($reqreq['msisdn'],$reqreq['callnoa'],$reqreq['callnob'],
             $reqreq['amount'],$reqreq['duration'],$reqreq['callid'],$reqreq['calltype'])=explode('~', $tmp);
        $reqreq['msisdn']=format_msisdn($reqreq['msisdn']);
        $funcname='voip_kf';
        $job=new job($funcname, json_encode($reqreq));
        $rep=$funclist[$funcname]($job);
        $rep=json_decode(iconv("gbk", "UTF-8", $rep), true);
        $rep=gbk_iconv($rep);
        $status=intval(substr($rep['error_code'],1));
        $tmp=$req['id'].'~'.$req['cmd'].'~'.$status.'~'.$rep['error_msg'];
    } else {
        Log::prn_log(ERROR, "opcode:{$req['cmd']} is not defined!");
        return "opcode:{$req['cmd']} is not defined!";
    }

    $result=sprintf('%04d~%s', 5+strlen($tmp), $tmp);
    Log::prn_log(NOTICE, "voip_reponse: $result");

    return $result;
}
