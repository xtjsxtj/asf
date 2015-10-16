<?php

/**
 * U2 gcdr话单合并入库进Elasticsearch
 */

require_once dirname(__FILE__).'/./batchfile.php';

$config = [
    'log_level' => NOTICE,
    'path' => '/usr1/data/cdr_u2/gcdr',
    'path_with_date' => true,
    'mysql' => ['host'    => '127.0.0.1',
                'port'    => 3306,
                'user'    => 'root',
                'passwd'  => 'cpyf',
                'name'    => 'shjf'
    ],
    'elasticsearch' => [
        'hosts' => [
            '172.16.18.116:9200',                 // IP + Port
        ]
    ]
];

$cdr = new BatchFile($config);
$cdr->on('procdir', 'procdir');
$cdr->on('procfile', 'procfile');

$db = $cdr->db;
$cdr->start();
        
function procdir($s, $curdate)
{        
    $params=[
        'index' => 'cdr-'.to_yyyyy_mm_dd($curdate),
        'type'  => 'u2_gcdr',
    ];           
    try 
    { 
        $params['ignore'] = 404;
        $s->client->indices()->deleteMapping($params);
    } 
    catch(Exception $e) 
    { 
        echo $e->getMessage() . "\n";
        $info = $s->client->transport->getLastConnection()->getLastRequestInfo();
        print_r($info);
        exit;
    }
    
    return $params;
}

function procfile($s, $file, $filedate)
{
    //log::prn_log(DEBUG, $file . " ...");
    $file_content = explode("\n", file_get_contents($file));
    $msisdn_list = array();
    $params = $s->params;    
    $params['body'] = array();
    $filedate = to_yyyyy_mm_dd($filedate);
    foreach($file_content as $line_str)
    {
        if ( $line_str == '' ) continue;
        if ( substr($line_str, 0, 2) == '00' ) continue;
        log::prn_log(DEBUG, $line_str);
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
        $line['end_time_stamp'] + 0;       
        $line['@timestamp'] = $line['@timestamp'] * 1000;
        $line['filedate'] = $filedate;
        $line['filename'] = basename($file); 
        
        $params['body'][] = ['index' => []];
        $params['body'][] = $line;   
        
        if (!array_key_exists($line['msisdn'], $msisdn_list)) $msisdn_list[$line['msisdn']]=1;
    }
    if ( count($params['body']) == 0 ) return true;   

    $msisdn_list_result = callno_get(array_keys($msisdn_list),$filedate);
    if ( $msisdn_list_result === false ) {
        echo "\nproc [$file] error!\n";
        exit;
    }
    
    $i = 0;     
    for($i=0;$i<count($params['body']);$i++) {
        if ( $i % 2 == 1 ) {
            $result = $msisdn_list_result[$params['body'][$i]['msisdn']];        
            $params['body'][$i]['vestss']   = $result['vestss'];
            $params['body'][$i]['bossid']   = $result['bossid'];
            $params['body'][$i]['permark']  = $result['permark'];
            $params['body'][$i]['subpp']    = $result['subpp'];                
        }        
    }    
    
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

/*
http://172.16.18.116:9200/_template/template_cdr
{
  "template" : "cdr*",
  "settings" : {
      "number_of_shards" : 1,
      "number_of_replicas": 0
  },
  "mappings" : {
    "u2_gcdr": {
        "properties": {
            "@timestamp": {
                "type": "long"
            },
            "apn": {
                "type": "string",
                "index": "no"
            },
            "bossid": {
                "type": "string",
                "index": "not_analyzed"
            },
            "calltype": {
                "type": "string",
                "index": "not_analyzed"
            },
            "chargingid": {
                "type": "long"
            },
            "datatype": {
                "type": "string",
                "index": "not_analyzed"
            },
            "datavol": {
                "type": "long"
            },
            "datavola": {
                "type": "long"
            },
            "datavolin": {
                "type": "long"
            },
            "datavolout": {
                "type": "long"
            },
            "duration": {
                "type": "long"
            },
            "end_time_stamp": {
                "type": "long"
            },
            "filedate": {
                "type": "string",
                "index": "not_analyzed"
            },
            "filename": {
                "type": "string",
                "index": "not_analyzed"
            },
            "ggsnadd": {
                "type": "string",
                "index": "no"
            },
            "imei": {
                "type": "string",
                "index": "not_analyzed"
            },
            "imsi": {
                "type": "string",
                "index": "not_analyzed"
            },
            "msisdn": {
                "type": "string",
                "index": "not_analyzed"
            },
            "op_id": {
                "type": "string",
                "index": "no"
            },
            "partialtype": {
                "type": "string",
                "index": "no"
            },
            "pdpadd": {
                "type": "string",
                "index": "no"
            },
            "permark": {
                "type": "string",
                "index": "not_analyzed"
            },
            "qos": {
                "type": "string",
                "index": "no"
            },
            "rattype": {
                "type": "string",
                "index": "no"
            },
            "sgsnadd": {
                "type": "string",
                "index": "no"
            },
            "sgsnchange": {
                "type": "string",
                "index": "no"
            },
            "startdate": {
                "type": "string",
                "index": "not_analyzed"
            },
            "starttime": {
                "type": "string",
                "index": "not_analyzed"
            },
            "subpp": {
                "type": "string",
                "index": "not_analyzed"
            },
            "tap_net_rate": {
                "type": "string",
                "index": "no"
            },
            "tap_tax_rate": {
                "type": "string",
                "index": "no"
            },
            "termind": {
                "type": "string",
                "index": "no"
            },
            "vestss": {
                "type": "string",
                "index": "not_analyzed"
            }
        }
    },
    "hjscdr": {
        "properties": {
            "@timestamp": {
                "type": "long"
            },
            "apn": {
                "type": "string",
                "index": "not_analyzed"
            },
            "charging_item": {
                "type": "string",
                "index": "not_analyzed"
            },
            "downlink": {
                "type": "long"
            },
            "ggsn": {
                "type": "string",
                "index": "not_analyzed"
            },
            "imsi": {
                "type": "string",
                "index": "not_analyzed"
            },
            "msisdn": {
                "type": "string",
                "index": "not_analyzed"
            },
            "pdp_address": {
                "type": "string",
                "index": "not_analyzed"
            },
            "rectype": {
                "type": "string",
                "index": "no"
            },
            "roaming_charge": {
                "type": "long"
            },
            "sgsn": {
                "type": "string",
                "index": "not_analyzed"
            },
            "startdate": {
                "type": "string",
                "index": "not_analyzed"
            },
            "starttime": {
                "type": "string",
                "index": "not_analyzed"
            },
            "totallink": {
                "type": "long"
            },
            "unit": {
                "type": "long"
            },
            "uplink": {
                "type": "long"
            }
        }
    }    
  }
}
*/