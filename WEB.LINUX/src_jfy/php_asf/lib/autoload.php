<?php

/**
 * @author jiaofuyou@qq.com
 * @date   2015-10-25
 */

require_once __DIR__.'/swoole.php';
require_once __DIR__.'/log.php';
require_once __DIR__.'/mysql.php';
require_once __DIR__.'/controller.php';
require_once __DIR__.'/route.php';
require_once __DIR__.'/config.php';
require_once __DIR__.'/fast-route/vendor/autoload.php';

spl_autoload_register(
    function($className) {  
        $file = BASE_PATH.'/controller' . "/$className.php";
        if ( file_exists($file) ) {
            log::prn_log(DEBUG, 'require_once: '. $file);
            require_once BASE_PATH.'/controller' . "/$className.php";
        }
    } 
);
