<?php

/**
 * U2 gcdr话单合并入库
 */

require_once dirname(__FILE__).'/./pub.php';
require_once dirname(__FILE__).'/./client.php';
require_once dirname(__FILE__).'/../lib/log.php';
require_once dirname(__FILE__).'/../lib/mysql.php';

//if (client_connect() !== true) exit;

if ( ($argc != 1) && ($argc != 3) )
{
    echo '-- u2_gcdr ' . "\n";
    echo '-- u2_gcdr <startdate> <enddate>' . "\n";
    exit;
}

if ( $argc == 1 )
{
    global $startdate;
    global $enddate;
    $startdate = date('Ymd', strtotime('-1 day'));
    $enddate = $startdate;
}
if ( $argc == 3 )
{
    global $startdate;
    global $enddate;
    $startdate = $argv[1];
    $enddate = $argv[2];
}

log::$log_level = NOTICE;

global $db;
$db=new mysqldb(array('host'    => '127.0.0.1',
                      'port'    => 3306,
                      'user'    => 'root',
                      'passwd'  => 'cpyf',
                      'name'    => 'shjf'
));
$db->connect();

$path = '/usr1/data/cdr_u2/gcdr';
$outpath = '/usr1/data/cdr_u2/gcdr_out';

function procfile($file, $filedate)
{
    global $fp;
    global $db;

    //log::prn_log(DEBUG, $file . " ...");
    $file_content = explode("\n", file_get_contents($file));
    $msisdn_list = array();
    $rec = array();    
    foreach($file_content as $line_str)
    {
        if ( $line_str == '' ) continue;
        if ( substr($line_str, 0, 2) == '00' ) continue;
        log::prn_log(DEBUG, $line_str);
        list($line['timestamp'],$line['datavola'],$line['datavol'],$line['duration'],$line['pdpadd'],
             $line['sgsnadd'],$line['partialtype'],$line['end_time_stamp'],$line['tap_net_rate'],
             $line['tap_tax_rate'],$line['calltype'],$line['rattype'],$line['msisdn'],$line['imsi'],
             $line['imei'],$line['sp_cgi'],$line['ggsnadd'],$line['op_id'],$line['qos'],$line['startdate'],
             $line['starttime'],$line['trac_date'],$line['trac_time'],$line['datavolout'],$line['datavolin'],
             $line['apn'],$line['sgsnchange'],$line['chargingid'],$line['termind'],$line['datatype'],$end)
             = explode(',', $line_str, 31);
        $rec[$line['chargingid']][] = $line;
        if (!array_key_exists($line['msisdn'], $msisdn_list)) $msisdn_list[$line['msisdn']]=1;
    }
    if ( count($rec) == 0 ) return true;
    
    $msisdn_list_result = callno_get(array_keys($msisdn_list),$filedate);
    if ( $msisdn_list_result === false ) {
        echo "\nproc [$file] error!\n";
        exit;
    }
    
    $sqlstr = 'insert into ggsncdr (timestamp,datavol,duration,pdpadd,sgsnadd,partialtype,calltype,'
            . 'rattype,msisdn,vestss,bossid,permark,subpp,imsi,imei,ggsnadd,startdate,starttime,datavolout,'
            . 'datavolin,apn,sgsnchange,chargingid,termind,datatype,filedate,filename) values ';    

    $i = 0;
    $outdata = '';   
    $filedate = to_yyyyy_mm_dd($filedate);
    foreach($rec as $chargingid => $value) {
        $line = current($rec[$chargingid]);
        while ($llll = next($rec[$chargingid])) {
            $line['datavol'] += intval($llll['datavol']);
            $line['duration'] += intval($llll['duration']);
            $line['datavolin'] += intval($llll['datavolin']);
            $line['datavolout'] += intval($llll['datavolout']);
            $line['datavola'] = intval($llll['datavola']);
        }
        $line['filedate'] = $filedate;
        $line['filename'] = basename($file);
        
        $result = $msisdn_list_result[$line['msisdn']];
        $line['vestss']   = $result['vestss'];
        $line['bossid']   = $result['bossid'];
        $line['permark']  = $result['permark'];
        $line['subpp']    = $result['subpp'];        
        $sqldat = "('{$line['timestamp']}','{$line['datavol']}','{$line['duration']}','{$line['pdpadd']}','{$line['sgsnadd']}',"
                . "'{$line['partialtype']}','{$line['calltype']}','{$line['rattype']}','{$line['msisdn']}','{$line['vestss']}',"
                . "'{$line['bossid']}','{$line['permark']}','{$line['subpp']}','{$line['imsi']}','{$line['imei']}','{$line['ggsnadd']}',"
                . "'{$line['startdate']}','{$line['starttime']}','{$line['datavolout']}','{$line['datavolin']}','{$line['apn']}',"
                . "'{$line['sgsnchange']}','{$line['chargingid']}','{$line['termind']}','{$line['datatype']}','{$line['filedate']}',"
                . "'{$line['filename']}')";
        $i++;
        if ($i == 1) $sqlstr .= $sqldat; else $sqlstr .= ','.$sqldat;   
        
        if ( ($line['permark']!='HK')&&($line['permark']!='HL') ) {
            $datavolout = ceil($line['datavolout']/1024.00);
            $datavolin = ceil($line['datavolin']/1024.00);
            $datavol = $datavolout + $datavolin;            
            $outline = sprintf("%d,%d,%d,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%.0f,%.0f,%s,%s,%d,%d,%s\n",
                $line['timestamp'],$datavol,$line['duration'],$line['pdpadd'],$line['sgsnadd'],
                $line['partialtype'],$line['calltype'],$line['rattype'],$line['msisdn'],$line['imsi'],
                $line['imei'],$line['ggsnadd'],$line['startdate'],$line['starttime'],$datavolout,
                $datavolin,$line['apn'],$line['sgsnchange'],$line['chargingid'],$line['termind'],
                $line['datatype']);
            $outdata .= $outline;
        }
    }    
    $line = null;
    $rec = null;
    $file_content = null;
    gc_collect_cycles(); //强制释放变量回收内存
    
    fprintf($fp, $outdata);
    if ( $db->insert_one($sqlstr) === false ) exit;  

    return true;
}

echo "\n";
log::prn_log(NOTICE, 'proc start ...');
$db->query('SET AUTOCOMMIT=0'); //支持mysql自动重连

for($curdate=$startdate;$curdate<=$enddate;$curdate=incdate($curdate,1))
{
    global $out;

    $yearmon = substr($curdate,0,6);
    $dir = "$path/$yearmon/$curdate";

    $dh = @opendir($dir);
    if ( $dh === false ) {
        log::prn_log(ERROR, "open $dir error!");
        exit;
    }

    $sqlstr = sprintf("delete from ggsncdr where filedate = '%s'", to_yyyyy_mm_dd($curdate));
    if ( $db->query($sqlstr) === false ) {
        log::prn_log(ERROR, 'delete data error!');
        exit;
    }

    $outdir = "$outpath/$yearmon/$curdate";
    if (!file_exists($outdir)) {
        if ( !mkdir($outdir, 0700, true) ) {
            log::prn_log(ERROR, "mkdir $outdir error!");
            exit;
        }
    }
    $outtempname = sprintf('%s/@GCDR_%s_%s', $outpath.'/temp', $curdate, '000000');
    $outfilename = sprintf('%s/GCDR_%s_%s', $outdir, $curdate, '000000');
    if (!($fp = fopen($outtempname, 'w'))) {
        log::prn_log(ERROR, "fopen $outtempname error!");
        exit;
    }

    $files = '';
    while (($file = readdir($dh)) !== false)
    {
        if ( $file == '.' || $file == '..' ) continue;
        $files[] = $dir.'/'.$file;
    }
    asort($files);
    $file_count = count($files);
    $i = 0;
    foreach($files as $file)
    {
        $i++;
        echo sprintf("\r%s [%d/%d] ...", $dir, $i, $file_count);
        if ( procfile($file, $curdate) === false ) exit;
    }
    closedir($dh);

    fclose($fp);
    if ( filesize($outtempname) == 0 ) unlink($outtempname);
    else rename($outtempname, $outfilename);
    
    echo "\n";
    $db->commit();    
}

log::prn_log(NOTICE, 'proc complete!');
