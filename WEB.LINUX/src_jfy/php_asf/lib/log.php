<?php

/**
 * Log封装类
 * @author jiaofuyou@qq.com
 * @date   2014-11-25
 */

const ERROR    = 5;
const WARNING  = 4;
const NOTICE   = 3;
const INFO     = 2;
const DEBUG    = 1;
const TRACE    = 0;

class Log
{
    public static $log_level=NOTICE;

    public static function prn_log($level, $msg)
    {
        $log_level_str = array('TRACE', 'DEBUG', 'INFO', 'NOTICE', 'WARNING', 'ERROR');
        if ( $level>=self::$log_level )
            echo '['.posix_getpid().'.'.date("Y-m-d H:i:s").']'.sprintf('%-9s ', "[$log_level_str[$level]]").$msg."\n";
    }
}

function error_handler($errno, $message, $file, $line){
    $error=array(
        1  => 'Error',
        2  => 'Warning',
        4  => 'Parse',
        8  => 'Notice',
        16 => 'Core Error',
        32 => 'Core Warning',
    );
    echo "MYPHP {$error[$errno]}:  {$message} in {$file} on line {$line}\n";
}
