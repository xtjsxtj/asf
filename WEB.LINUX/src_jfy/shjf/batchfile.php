<?php

/**
 * 批量文件处理类
 * @author jiaofuyou@qq.com
 * @date   2015-08-31
 */

require_once dirname(__FILE__).'/./vendor/autoload.php';
require_once dirname(__FILE__).'/./pub.php';
require_once dirname(__FILE__).'/../lib/log.php';
require_once dirname(__FILE__).'/../lib/mysql.php';

class BatchFile
{
    private $config;
    private $startdate,$enddate;
    private $on_func;
    
    public $db;
    public $client;
    public $params;
    
    public function __construct($config)
    {
        global $argc,$argv;
        if ( ($argc != 1) && ($argc != 3) )
        {
            echo "-- $argv[0] \n";
            echo "-- $argv[0] <startdate> <enddate>\n";
            exit;
        }

        if ( $argc == 1 )
        {
            $this->startdate = date('Ymd', strtotime('-1 day'));
            $this->enddate = $this->startdate;
        }
        if ( $argc == 3 )
        {
            $this->startdate = $argv[1];
            $this->enddate = $argv[2];
        }

        $this->config = $config;
        Log::$log_level=$this->config['log_level'];        

        if ( isset($this->config['mysql']) ) {
            $this->db=new mysqldb($this->config['mysql']);
            $this->db->connect();            
        }
 
        if ( isset($this->config['elasticsearch']) ) {        
            $this->client = new Elasticsearch\Client($this->config['elasticsearch']);
        }
    }

    public function on($event, $func)
    {
        $this->on_func[$event]=$func;
    }

    public function start(){
        echo "\n";
        log::prn_log(NOTICE, 'proc start ...');

        if ( $this->config['path_with_date'] ) {
            for($curdate=$this->startdate;$curdate<=$this->enddate;$curdate=incdate($curdate,1))
            {
                $yearmon = substr($curdate,0,6);
                $dir = "{$this->config['path']}/$yearmon/$curdate";

                $dh = @opendir($dir);
                if ( $dh === false ) {
                    log::prn_log(ERROR, "open $dir error!");
                    exit;
                }

                $this->params = [];
                if ( isset($this->on_func['procdir']) ) $this->params = call_user_func($this->on_func['procdir'], $this, $curdate);

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
                    log::prn_log(NOTICE, "$dir [$i/$file_count] ...");
                    call_user_func($this->on_func['procfile'], $this, $file, $curdate);
                }
                closedir($dh);

                echo "\n";   
            }
        }

        log::prn_log(NOTICE, 'proc complete!');
    }
}
