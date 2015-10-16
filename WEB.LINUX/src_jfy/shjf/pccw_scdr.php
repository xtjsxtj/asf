<?php

/**
 * PCCW scdr话单合并入库
 * 数据库插入采用批量提交方式：一个文件一个insert语句插入多条，一天的文件一commit
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

global $db;
$db=new mysqldb(array('host'    => '127.0.0.1',
                      'port'    => 3306,
                      'user'    => 'root',
                      'passwd'  => 'cpyf',
                      'name'    => 'shjf'
));
$db->connect();

$path = '/usr1/data/cdr_pccw_scdr';

function procfile($file, $filedate)
{
    global $db;
    
    $filedate = to_yyyyy_mm_dd($filedate);
    
    //echo $file . " ...\n";
    echo '.';
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
             $line['apn'],$line['sgsnchange'],$line['chargingid'],$line['termind'],$line['datatype'],$end) 
             = explode(',', $line_str, 31);   
        $rec[$line['chargingid']][] = $line;
    }
    
    $sqlstr = 'insert into sgsncdr (timestamp,datavol,duration,pdpadd,sgsnadd,partialtype,calltype,'
            . 'rattype,msisdn,vestss,bossid,permark,subpp,imsi,imei,ggsnadd,startdate,starttime,datavolout,'
            . 'datavolin,apn,sgsnchange,chargingid,termind,datatype,filedate,filename) values ';
    
    $i=0;
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
                
        if (false /*($result = callno_get(substr($line['msisdn'], 3)))*/ === false) {
            $line['vestss']  = '';
            $line['bossid']  = '';
            $line['permark'] = '';
            $line['subpp']   = '';
        } else {                
            $line['vestss']   = $result['vestss'];
            $line['bossid']   = $result['bossid'];
            $line['permark']  = $result['permark'];
            $line['subpp']    = $result['subpp'];
        }   
        
        $sqldat = "('{$line['timestamp']}','{$line['datavol']}','{$line['duration']}','{$line['pdpadd']}','{$line['sgsnadd']}',"
                . "'{$line['partialtype']}','{$line['calltype']}','{$line['rattype']}','{$line['msisdn']}','{$line['vestss']}',"
                . "'{$line['bossid']}','{$line['permark']}','{$line['subpp']}','{$line['imsi']}','{$line['imei']}','{$line['ggsnadd']}',"
                . "'{$line['startdate']}','{$line['starttime']}','{$line['datavolout']}','{$line['datavolin']}','{$line['apn']}',"
                . "'{$line['sgsnchange']}','{$line['chargingid']}','{$line['termind']}','{$line['datatype']}','{$line['filedate']}',"
                . "'{$line['filename']}')";
        $i++;
        if ($i == 1) $sqlstr .= $sqldat; else $sqlstr .= ','.$sqldat;                                        
    }         
    $line = null;
    $rec = null;
    $file_content = null;
    gc_collect_cycles(); //强制释放变量回收内存
    
    if ( $db->insert_one($sqlstr) === false ) exit;  
    
    return true;
}

$db->query('SET AUTOCOMMIT=0'); //支持mysql自动重连 

for($curdate=$startdate;$curdate<=$enddate;$curdate=incdate($curdate,1))
{
    global $out;
    
    $yearmon = substr($curdate,0,6);
    $dir = "$path/$yearmon/$curdate/";
    echo $dir . " ...\n";    
    
    $sqlstr = sprintf("delete from sgsncdr where filedate = '%s'", to_yyyyy_mm_dd($curdate));    
    if ( $db->query($sqlstr) === false ) {
        echo 'delete data error!' . "\n";
        exit;
    }    
      
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
        if ( procfile($file, $curdate) === false ) exit;
    }
    closedir($dh);
    echo "\n";
       
    $db->commit();    
}

echo 'end!'."\n";
