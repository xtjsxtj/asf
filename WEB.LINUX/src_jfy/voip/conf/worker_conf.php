<?php

require_once dirname(__FILE__).'/../protocol/request.php';
require_once dirname(__FILE__).'/../apply/apply.php';
require_once dirname(__FILE__).'/../apply/boss.php';
require_once dirname(__FILE__).'/../apply/voipgw.php';
require_once dirname(__FILE__).'/../common/pub.php';
require_once dirname(__FILE__).'/../common/func.php';

class Worker_conf{
    public static $config=array(
        'log_level' => DEBUG,
        'server' => array(
            'mysql_host' => '127.0.0.1',
            'mysql_port' => 3306,
            'mysql_user' => 'root',
            'mysql_passwd' => 'cpyf',
            'mysql_db' => 'voip',
        ),
        'funclist' => array(
            'create_user' => 'apply_with_tran',
            'set_secure' => 'apply_with_tran',
            'get_user' => 'apply_with_common',
            'recharge' => 'apply_with_tran',
            'voip_jq' => 'apply_with_common',
            'voip_kf' => 'apply_with_common',
            'voip_kf_async' => 'apply_with_common',
            'test' => 'test',
        )
    );
}
