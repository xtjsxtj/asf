<?php

/**
 * 对一数组所有元素或一个字符串进行urlencode转码操作
 * @param string/array $str
 * @return string/array 转码结果字符串或数组
 */
function url_encode($str) {
    if (is_array($str)) {
        foreach ($str as $key => $value) {
            $str[urlencode($key)] = url_encode($value);
        }
    } else {
        //if ( !is_bool($str) ) $str = urlencode($str);
        if ( is_string($str) ) $str = urlencode($str);
    }

    return $str;
}

/**
 * 将一数组进行jsonencode编码
 * @param array $array 键值数组
 * @param boolean $keyval 是否打包成key:value格式的json目标，若为false则打包成josn数组
 * @return type
 */
function encode_json($arrval, $keyval=true)
{
    $str = url_encode($arrval);
    if ( $keyval == true ) {
      return urldecode(json_encode($str));
    } else {
      foreach($str as $key=>$value) $arr[]=$value;
      return urldecode(json_encode($arr));
    }
}

/**
 * 将一数组或字符串转进行UTF-8到bgk转码
 * @param array/string $str
 * @return string 转码后结果
 */
function gbk_iconv($str)
{
    if (is_array($str)) {
        foreach ($str as $key => $value) {
            $str[$key] = gbk_iconv($value);
        }
    } else {
        $str = iconv("UTF-8", "gbk", $str);
    }

    return $str;
}

/**
 * 检查一数组参数中，是否包含所需键值key
 * @param array $param 数组参数
 * @param array $varnames 必须包含的键值key列表
 * @return boolean
 */
function check_array($param, $varnames)
{
    $ret=true;
    foreach ($varnames as $varname) {
        if ( !isset($param[$varname]) ) {
            Log::prn_log(ERROR, "param is error, <$varname> is not exists!");
            $ret=false;
        }
    }

    return $ret;
}
