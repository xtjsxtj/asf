<?php

require_once dirname(__FILE__).'/../lib/log.php';
require_once dirname(__FILE__).'/../lib/mysql.php';

//set_error_handler('error_handler');

Log::$log_level = DEBUG;

$db=new mysqldb(array('host'    => 'localhost',
                      'port'    => 3306,
                      'user'    => 'root',
                      'passwd'  => 'cpyf',
                      'name'    => 'test',
                      'persistent' => true, //MySQL³¤Á¬½Ó
));
$db->connect();

sleep(30);

$result = $db->select_more('select * from test limit 10');
var_dump($result);

sleep(30);
