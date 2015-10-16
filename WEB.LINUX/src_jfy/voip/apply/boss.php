<?php

require_once dirname(__FILE__).'/../common/func.php';

function call_boss($commandid, $body)
{
    //端口8888
    $service_port = 8888;

    //本地
    $address = '172.16.18.18';

    //创建 TCP/IP socket
    $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
    if ($socket == false) {
      Log::prn_log(ERROR, 'socket_create failed: ' . socket_strerror(socket_last_error()));
      return false;
    } else {
      Log::prn_log(DEBUG, 'socket create success!');
    }
    socket_set_option($socket,SOL_SOCKET,SO_RCVTIMEO,array("sec"=>5, "usec"=>0 ));
    socket_set_option($socket,SOL_SOCKET,SO_SNDTIMEO,array("sec"=>5, "usec"=>0 ));

    $result = @socket_connect($socket, $address, $service_port);
    if ($result == false) {
      Log::prn_log(ERROR, 'socket_connect failed: ('.socket_last_error().') ' . socket_strerror(socket_last_error()));
      return false;
    } else {
      Log::prn_log(DEBUG, 'socket connect success!');
    }

    //发送命令
    $headerbody = array();
    $header = array();
    $header['Version'] = '10';
    $header['Commandid'] = $commandid;
    $header['Seq'] = 1;
    $header['Pkgseq'] = 1;
    $header['Overflag'] = '1';
    $headerbody['Message_Header'] = $header;
    $headerbody['Message_Body'] = $body;

    $jsondata = json_encode($headerbody);
    Log::prn_log(INFO, '> '.$jsondata);

    $in = pack('N', strlen($jsondata)+4);
    socket_write($socket, $in, 4);
    $len=socket_write($socket, $jsondata, strlen($jsondata));
    if ( $len === false ) {
        Log::prn_log(ERROR, 'socket_write failed: ' . socket_strerror($socket));
        return false;
    }
    Log::prn_log(DEBUG, 'socket_write ok!');

    Log::prn_log(DEBUG, 'read socket ...');
    $out = socket_read($socket, 4);
    $len = unpack('N', $out);
    $out = socket_read($socket, $len[1]-4);
    if ( $out === false ) {
        Log::prn_log(ERROR, 'socket_read failed: ' . socket_strerror($socket));
        return false;
    }
    Log::prn_log(INFO, '< '.$out);

    Log::prn_log(DEBUG, 'close socket!');
    socket_shutdown($socket, 2);
    socket_close($socket);

    $result = json_decode(iconv("gbk", "UTF-8", $out), true);
    if ( $result === NULL ) {
        Log::prn_log(ERROR, "boss result is error, json_decode is NULL!");
        return false;
    }
    $result=gbk_iconv($result);
    if ( !isset($result['Message_Body']['Result']['Result_Code']) ||
         !isset($result['Message_Body']['Result']['Result_Memo']) ) {
        Log::prn_log(ERROR, 'call boss return error: call boss return error: json field error!');
        return false;
    }

    //Log::prn_log('call boss ok!');

    return $result['Message_Body'];
}

function get_msisdn_info($msisdn, &$return, $flag='QUERY')
{
    if ((strlen($msisdn)==11)&&(substr($msisdn,0,3)=='852')) $body['Callno'] = substr($msisdn,3);
    if ((strlen($msisdn)==13)&&(substr($msisdn,0,2)=='86')) $body['Callno'] = substr($msisdn,2);
    $body['Flag'] = $flag;

    if ( ($result=call_boss(101, $body)) === false ) {
        Log::prn_log(ERROR, 'call boss error!');
        $return='call boss error!';
        return -1;
    }
    if ( $result['Result']['Result_Code'] != 0 ) {
        Log::prn_log(ERROR, "call boss return error: {$result['Result']['Result_Memo']}");
        $return=$result['Result']['Result_Memo'];
        return false;
    }
    if ( !check_array($result,array('Type','Imsi','Bagfee','Bagtime','RateId')) ) {
        Log::prn_log(ERROR, 'call boss return error: json field error!');
        $return='call boss return error: json field error!';
        return false;
    }
    $return = array(
        'msisdn' => $msisdn,
        'zgtflag' => true,
        'usertype' => $result['Type'],
        'imsi' => $result['Imsi'],
        'activetime' => '',
        'amount' => $result['Bagfee'],
        'validdate' => $result['Bagtime'],
        'status' => 'A',
        'nextkfdate' => '',
        'rate_code' => $result['RateId'],
    );

    return true;
}

function check_recharge($cardnum,&$return)
{
    $body['Cardnum'] = $cardnum;

    if ( ($result=call_boss(102, $body)) === false ) {
        Log::prn_log(ERROR, 'call boss error!');
        $return='call boss error!';
        return -1;
    }
    if ( $result['Result']['Result_Code'] != 0 ) {
        Log::prn_log(ERROR, "call boss return error: {$result['Result']['Result_Memo']}");
        $return=$result['Result']['Result_Memo'];
        return false;
    }
    if ( !isset($result['Fee']) ) {
        Log::prn_log(ERROR, 'call boss return error: json field error!');
        $return='call boss return error: json field error!';
        return false;
    }
    $return['amount']=$result['Fee'];

    return true;
}

function apply_recharge($msisdn,$cardnum,&$return)
{
    $body['CZCallno'] = (strlen($msisdn)==11)&&(substr($msisdn,0,3)=='852')?substr($msisdn,3):$msisdn;
    $body['Callno'] = $body['CZCallno'];
    $body['Cardnum'] = $cardnum;

    if ( ($result=call_boss(103, $body)) === false ) {
        Log::prn_log(ERROR, 'call boss error!');
        $return='call boss error!';
        return -1;
    }
    if ( $result['Result']['Result_Code'] != 0 ) {
        Log::prn_log(ERROR, "call boss return error: {$result['Result']['Result_Memo']}");
        $return=$result['Result']['Result_Memo'];
        return false;
    }

    return true;
}

function update_recharge($msisdn,$cardnum,&$return)
{
    $body['CZCallno'] = (strlen($msisdn)==11)&&(substr($msisdn,0,3)=='852')?substr($msisdn,3):$msisdn;
    $body['Cardnum'] = $cardnum;

    if ( ($result=call_boss(104, $body)) === false ) {
        Log::prn_log(ERROR, 'call boss error!');
        $return='call boss error!';
        return -1;
    }
    if ( $result['Result']['Result_Code'] != 0 ) {
        Log::prn_log(ERROR, "call boss return error: {$result['Result']['Result_Memo']}");
        $return=$result['Result']['Result_Memo'];
        return false;
    }

    return true;
}

function apply_charging($req,&$return)
{
    $body['AcctNo'] = (strlen($req['msisdn'])==11)&&(substr($req['msisdn'],0,3)=='852')?substr($req['msisdn'],3):$req['msisdn'];
    $body['CallnoA'] = $req['callnoa'];
    $body['CallnoB'] = $req['callnob'];
    $body['Fee'] = $req['amount'];
    $body['Duration'] = $req['duration'];
    $body['CallID'] = $req['callid'];
    $body['CallFlag'] = $req['calltype'];
    $body['Secure'] = $req['secure'];

    if ( ($result=call_boss(105, $body)) === false ) {
        Log::prn_log(ERROR, 'call boss error!');
        $return='call boss error!';
        return -1;
    }
    if ( $result['Result']['Result_Code'] != 0 ) {
        Log::prn_log(ERROR, "call boss return error: {$result['Result']['Result_Memo']}");
        $return=$result['Result']['Result_Memo'];
        return false;
    }

    return true;
}
