<?php

/**
 * PCCW scdr话单合并入库进Elasticsearch
 * 数据库插入采用批量提交方式：一个文件一个bulk
 */

require_once dirname(__FILE__).'/./batchfile.php';

$config = [
    'log_level' => NOTICE,
    'path' => '/usr1/data/cdr_pccw_scdr',
    'path_with_date' => true,
    'elasticsearch' => [
        'hosts' => [
            '172.16.18.116:9200',                 // IP + Port
        ]
    ]
];

$cdr = new BatchFile($config);
$cdr->on('procdir', 'procdir');
$cdr->on('procfile', 'procfile');
$cdr->start();

function procdir($s, $curdate)
{    
    $params=[
        'index' => 'cdr-'.to_yyyyy_mm_dd($curdate),
        'type'  => 'pccw_scdr',
    ];           
    try 
    { 
        $params['ignore'] = 404;
        $s->client->indices()->deleteMapping($params);
    } 
    catch(Exception $e) 
    { 
        echo $e->getMessage() . "\n";
        $info = $s->client->transport->getLastConnection()->  getLastRequestInfo();
        print_r($info);
        exit;
    }
    
    return $params;
}

function procfile($s, $file, $filedate)
{
    $filedate = to_yyyyy_mm_dd($filedate);    
    $file_content = explode("\n", file_get_contents($file));
    $params = $s->params;    
    $params['body'] = array();    
    foreach($file_content as $line_str)
    {
        if ( $line_str == '' ) continue;
        if ( substr($line_str, 0, 2) == '00' ) continue;          
        list($line['@timestamp'],$line['datavola'],$line['datavol'],$line['duration'],$line['pdpadd'],
             $line['sgsnadd'],$line['partialtype'],$line['end_time_stamp'],$line['tap_net_rate'],
             $line['tap_tax_rate'],$line['calltype'],$line['rattype'],$line['msisdn'],$line['imsi'],
             $line['imei'],$line['sp_cgi'],$line['ggsnadd'],$line['op_id'],$line['qos'],$line['startdate'],
             $line['starttime'],$line['trac_date'],$line['trac_time'],$line['datavolout'],$line['datavolin'],
             $line['apn'],$line['sgsnchange'],$line['chargingid'],$line['termind'],$line['datatype'],$end) 
             = explode(',', $line_str, 31);  
        $line['chargingid'] = $line['chargingid'] + 0;
        $line['datavola'] = $line['datavola'] + 0;
        $line['datavol'] = $line['datavol'] + 0;
        $line['duration'] = $line['duration'] + 0;
        $line['datavolin'] = $line['datavolin'] + 0;
        $line['datavolout'] = $line['datavolout'] + 0;   
        $line['@timestamp'] = $line['@timestamp'] * 1000;
        $line['filedate'] = $filedate;
        $line['filename'] = basename($file); 
        
        $params['body'][] = ['index' => []];
        $params['body'][] = $line;  
    }
    if ( count($params['body']) == 0 ) return true; 

    try 
    { 
        $s->client->bulk($params);
    } 
    catch(Exception $e) 
    { 
        echo $e->getMessage() . "\n";
        $info = $s->client->transport->getLastConnection()->getLastRequestInfo();
        print_r($info);
        exit;
    }
 
    $file_content = null;
    $params = null;
    gc_collect_cycles(); //强制释放变量回收内存

    return true;
}
