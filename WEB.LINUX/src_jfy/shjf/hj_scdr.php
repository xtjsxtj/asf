<?php

require 'vendor/autoload.php';

function guid(){
    if (function_exists('com_create_guid')){
        return com_create_guid();
    }else{
        mt_srand((double)microtime()*10000);//optional for php 4.2.0 and up.
        $charid = strtoupper(md5(uniqid(rand(), true)));
        $hyphen = chr(45);// "-"
        $uuid = chr(123)// "{"
                .substr($charid, 0, 8).$hyphen
                .substr($charid, 8, 4).$hyphen
                .substr($charid,12, 4).$hyphen
                .substr($charid,16, 4).$hyphen
                .substr($charid,20,12)
                .chr(125);// "}"
        return $uuid;
    }
}

$params['hosts'] = [
    '172.16.18.116:9200',                 // IP + Port
];
$client = new Elasticsearch\Client($params);

$params=[
    'type'  => 'hjscdr',
];

$dir = "./";
$dh = @opendir($dir);
if ( $dh === false ) {
    echo "open $dir error!\n";
    exit;
}

$files = array();
while (($file = readdir($dh)) !== false)
{
    if ( substr($file,0,9) != 'CDHGCHKT1' ) continue;
    $files[] = $dir.$file;
}
asort($files);
foreach($files as $file)
{
    echo "$file ...\n";
        
    $file_content = explode("\r\n", file_get_contents($file));
    $line_count = count($file_content)-2;
    for($i=0;$i<$line_count;$i++){    
        if ( $i % 1000 == 0 ) echo "$i/$line_count ...\n";
        $line_str = $file_content[$i];
        if ( substr($line_str,0,2) != '61' ) continue;
        $data = unpack('a2rectype/a15imsi/a15msisdn/a15pdp_address/a15sgsn/a15ggsn/a8startdate/a6starttime'
                     .'/a6unit/a10uplink/a10downlink/a64apn/a9roaming_charge/a1charging_item/a49reserved/', $line_str);
        foreach($data as $key => $value) {$data[$key] = trim($value); if ($data[$key] == '') $data[$key] = ' ';}
        $startdate = substr($data['startdate'],0,4) . '-' . substr($data['startdate'],4,2) . '-' . substr($data['startdate'],6,2);
        $starttime = substr($data['starttime'],0,2) . ':' . substr($data['starttime'],2,2) . ':' . substr($data['starttime'],4,2);
        $data['unit'] = $data['unit'] + 0;
        $data['uplink'] = $data['uplink'] + 0;
        $data['downlink'] = $data['downlink'] + 0;
        $data['totallink'] = $data['uplink'] + $data['downlink'];
        $data['roaming_charge'] = $data['roaming_charge'] + 0;
        $data['@timestamp'] = strtotime($startdate . ' ' . $starttime) * 1000;
        
        $params['index'] = 'cdr-'.$startdate;
        $params['body'] = $data;
        
        try 
        { 
            $client->index($params);
        } 
        catch(Exception $e) 
        { 
            echo $e->getMessage() . "\n";
            $info = $client->transport->getLastConnection()->getLastRequestInfo();
            print_r($info);                                                       
        }
    }
    echo "$i/$line_count ...\n";
}

echo "end!\n";


/*
http://172.16.18.116:9200/_template/template_cdr
{
  "template" : "cdr*",
  "settings" : {
      "number_of_shards" : 1,
      "number_of_replicas": 0
  },
  "mappings" : {
    "hjscdr" : {   
      "properties": {   
        "rectype" : {
          "type": "string",
          "index": "no"
        },
        "imsi" : {
          "type": "string",
          "index": "not_analyzed"
        },
        "msisdn" : {
          "type": "string",
          "index": "not_analyzed"
        },
        "pdp_address" : {
          "type": "string",
          "index": "not_analyzed"
        },
        "sgsn" : {
          "type": "string",
          "index": "not_analyzed"
        },
        "ggsn" : {
          "type": "string",
          "index": "not_analyzed"
        },
        "startdate" : {
          "type": "string",
          "index": "not_analyzed"
        },
        "starttime" : {
          "type": "string",
          "index": "not_analyzed"
        },
        "@timestamp" : {
          "type": "long"
        },
        "unit" : {
          "type": "long"
        },
        "uplink" : {
          "type": "long"
        },
        "downlink" : {
          "type": "long"
        },
        "totallink" : {
          "type": "long"
        },        
        "apn" : {
          "type": "string",
          "index": "not_analyzed"
        },
        "roaming_charge" : {
          "type": "long"
        },
        "charging_item" : {
          "type": "string",
          "index": "not_analyzed"
        }
      }
    }
  }
}
*/