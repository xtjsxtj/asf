<?php

/**
 * 统计U2 gcdr话单
 * 按日汇总流量
 */

include_once('./pub.php');
include_once('./client.php');

//if (client_connect() !== true) exit;
//if (($result = callno_get('65194003')) === false) exit;
//var_dump($result);
//exit;

if ( $argc > 3 )
{
    echo '-- u2_gcdr_tj ' . "\n";
    echo '-- u2_gcdr_tj <yyyymm>' . "\n";
    echo '-- u2_gcdr_tj <startdate> <enddate>' . "\n";
    exit;
}

if ( $argc == 1 )
{
    global $startdate;
    global $enddate;
    $yyyymm = date('Ym', strtotime('-1 month'));
    month_start_end($yyyymm, $startdate, $enddate);
}
if ( $argc == 2 )
{
    global $startdate;
    global $enddate;
    $yyyymm = $argv[1];
    month_start_end($yyyymm, $startdate, $enddate);
}
if ( $argc == 3 )
{
    global $startdate;
    global $enddate;
    $startdate = $argv[1];
    $enddate = $argv[2];
}

$path = '/usr1/data/cdr_u2_gcdr';

for($curdate=$startdate;$curdate<=$enddate;$curdate=incdate($curdate,1))
{
    global $out;
    
    $yearmon = substr($curdate,0,6);
    $dir = "$path/$yearmon/$curdate/";
    echo $dir . " ...\n";
    
    $dh = @opendir($dir);
    if ( $dh === false ) {
        echo "open $dir error!\n";
        exit;
    }

    $files = '';
    while (($file = readdir($dh)) !== false)
    {
        if ( $file == '.' || $file == '..' ) continue;
        $files[] = $dir.$file;
    }
    asort($files);
    foreach($files as $file)
    {
        //echo $file . "\n";
        $file_content = explode("\n", file_get_contents($file));
        foreach($file_content as $line_str)
        {
            if ( $line_str == '' ) continue;
            if ( substr($line_str, 0, 2) == '00' ) continue;
            //echo $line_str . "\n";
            list($line['timestamp'],$line['datavola'],$line['datavol'],$line['duration'],$line['pdpadd'],
                 $line['sgsnadd'],$line['partialtype'],$line['end_time_stamp'],$line['tap_net_rate'],
                 $line['tap_tax_rate'],$line['calltype'],$line['rattype'],$line['msisdn'],$line['imsi'],
                 $line['imei'],$line['sp_cgi'],$line['ggsnadd'],$line['op_id'],$line['qos'],$line['startdate'],
                 $line['starttime'],$line['trac_date'],$line['trac_time'],$line['datavolout'],$line['datavolin'],
                 $line['apn'],$line['sgsnchange'],$line['chargingid'],$line['termind'],$line['datatype']) 
                 = explode(',', $line_str, 30);
            //echo "{$line['timestamp']},{$line['startdate']},{$line['datavol']},{$line['duration']}\n";            
            @$out[$line['startdate']] += $line['datavol'];
        }
    }
    closedir($dh);
}

echo 'end!'."\n";

echo 'result:' . "\n";
ksort($out);
foreach($out as $key => $value) echo $key . ':' . $value . "\n";
