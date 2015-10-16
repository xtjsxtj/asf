<?php

//日志处理
function prn_log($message){
    echo '['.posix_getpid().'.'.date("Y-m-d H:i:s").'] - '.$message."\n";
}

function assign_result($errcode, $msg)
{
    if ( is_array($msg) ) {
        $result=array('error_code'=>$errcode)+$msg;
    } else {
        $result['error_code']=$errcode;
        $result['error_msg']=$msg;
    }

    return encode_json($result);
}

function assing_return($return, $msg)
{
    $result['return']=$return;
    $result['result']=$msg;

    return encode_json($result);
}

function url_encode($str) {
    if (is_array($str)) {
        foreach ($str as $key => $value) {
            $str[urlencode($key)] = url_encode($value);
        }
    } else {
        if ( !is_bool($str) ) $str = urlencode($str);
    }

    return $str;
}

function encode_json($str, $keyval=true)
{
  $str = url_encode($str);
  if ( $keyval == true ) {
    return urldecode(json_encode($str));
  } else {
    foreach($str as $key=>$value) $arr[]=$value;
    return urldecode(json_encode($arr));
  }
}



