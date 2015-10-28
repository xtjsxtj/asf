<?php

/**
 * MySQLi数据库封装类
 * @author jiaofuyou@qq.com
 * @date   2014-11-25
    config = array (
        'host'       => 'localhost',  //mysql主机
        'port'       => 3306,         //mysql端口
        'user'       => 'user',       //mysql用户，必须参数
        'passwd'     => 'password',   //mysql密码，必须参数
        'name'       => 'dbname',     //数据库名称，必须参数
        'persistent' => false,        //MySQL长连接
        'charset'    => 'utf8',       //连接数据库字符集
        'sqls'       => 'set wait_timeout=24*60*60*31;set wait_timeout=24*60*60*31'  
                                      //连接数据库后需要执行的SQL语句,以';'分隔的多条语句
    )
 */
class mysqldb extends mysqli
{
    public $conn = null;
    public $config;

    public function __construct($db_config)
    {
        if ( !isset($db_config['host']) ) $db_config['host'] = 'localhost';
        if ( !isset($db_config['port']) ) $db_config['port'] = 3306;
        $this->config = $db_config;
        if ( isset($db_config['persistent'])?$db_config['persistent']:false )
        {
            $this->config['host'] = 'p:'.$this->config['host'];
            //$host：Prepending host by p: opens a persistent connection,'p:172.16.18.114'
            //must is mysqli->close()后持久连接才会被保持
        }
    }

    public function connect($host = NULL, $user = NULL, $password = NULL, $database = NULL, $port = NULL, $socket = NULL)
    {
        $db_config = $this->config;
        @parent::connect($db_config['host'], $db_config['user'], $db_config['passwd'], $db_config['name'], $db_config['port']);
        if( $this->connect_errno ){
            Log::prn_log(ERROR, "database connect failed: ".$this->connect_error."!");
            return false;
        }
        Log::prn_log(INFO, "database connect ok ({$db_config['host']},{$db_config['port']})!");
        if ( isset($db_config['charset']) ) {
            Log::prn_log(INFO, "set charset names {$db_config['charset']}");
            $this->query("set names {$db_config['charset']}");
        }       
        if ( isset($db_config['sqls']) ) {
            Log::prn_log(INFO, "set charset names {$db_config['charset']}");
            $this->query("set names {$db_config['charset']}");
        }    
        if ( isset($db_config['sqls']) ) {
            $sqls = explode(";", $db_config['sqls']);
            foreach($sqls as $sql)
            {
                Log::prn_log(INFO, "$sql");
                $this->query($sql);
            }
        } 
        
        return true;
    }

    /**
     * 执行一个SQL语句
     * @param string $sql 执行的SQL语句
     * @return result(object) | false
     */
    public function query($sql)
    {
        $result = false;
        for ($i = 0; $i < 2; $i++)
        {
            $result = @parent::query($sql);
            if ($result === false)
            {
                if ($this->errno == 2013 or $this->errno == 2006)
                {
                    Log::prn_log(ERROR, "[{$this->errno}]{$this->error}, reconnect ...");
                    $r = $this->checkConnection();
                    if ($r === true) continue;
                }
                else
                {
                    return false;
                }
            }
            break;
        }
        if ($result === false)
        {
            Log::prn_log(ERROR, "mysql connect lost, again still failed, {$this->errno}, {$this->error}");
            return false;
        }

        return $result;
    }

    /**
     * 检查数据库连接,是否有效，无效则重新建立
     */
    protected function checkConnection()
    {
        if (!@$this->ping())
        {
            $this->close();
            return $this->connect();
        }
        return true;
    }

    /**
     * 查询唯一记录
     * @param string $sql 执行的SQL语句
     * @return row(array) | false
     */
    public function select_one($sqlstr,$flag=true){
        if ( !($result = $this->query($sqlstr)) ) {
            Log::prn_log(ERROR, "select_one,($sqlstr) error,$this->errno,$this->error!");
            return false;
        }
        if ( $result->num_rows == 0 ) {
            if ($flag) Log::prn_log(ERROR, "select_one,($sqlstr) not found!");
            return false;
        } else if ( $result->num_rows > 1 ) {
            if ($flag) Log::prn_log(ERROR, "select_one ($sqlstr) mulit found!");
            return false;
        }
        $row = $result->fetch_assoc();
        Log::prn_log(INFO, 'select_one ok:'.json_encode($row));
        return $row;
    }

    /**
     * 查询多条记录
     * @param string $sql 执行的SQL语句
     * @return result(array) | false
     */
    public function select_more($sqlstr){
        if ( !($result = $this->query($sqlstr)) ) {
            Log::prn_log(ERROR, "select_more,($sqlstr) error,$this->errno,$this->error!");
            return false;
        }
        for ($res = array(); $tmp = $result->fetch_assoc();) $res[] = $tmp;

        return $res;
    }

    /**
     * 插入单条记录
     * @param string $sql 执行的SQL语句
     * @return true | false
     * $this->insert_id 为自增字段ID
     */
    public function insert_one($sqlstr,$flag=true){
        if ( !($result = $this->query($sqlstr)) ) {
            Log::prn_log(ERROR, "insert_one,($sqlstr) error,{$this->errno},{$this->error}!");
            return false;
        }
        if ($flag) Log::prn_log(INFO, 'insert_one ok: '.$sqlstr);

        return true;
    }

    /**
     * 更新单条记录
     * @param string $sql 执行的SQL语句
     * @return true | false
     * @$this->affected_rows 为更新记录数
     */
    public function update_one($sqlstr){
        if ( !($result = $this->query($sqlstr)) ) {
            Log::prn_log(ERROR, "update_one,($sqlstr) error,{$this->errno},{$this->error}!");
            return false;
        }
        $rows = $this->affected_rows;
        if ( $rows != 1 ) {
          Log::prn_log(ERROR, "update_one ,($sqlstr) affected_rows is $rows!");
          return false;
        }
        Log::prn_log(INFO, 'update_one ok: '.$sqlstr);

        return true;
    }

    /**
     * 更新多条记录
     * @param string $sql 执行的SQL语句
     * @return true | false
     */
    public function update_more($sqlstr){
        if ( !($result = $this->query($sqlstr)) ) {
            Log::prn_log(ERROR, "update_more,($sqlstr) error,{$this->errno},{$this->error}!");
            return false;
        }
        Log::prn_log(INFO, 'update_more ok: '.$sqlstr);
        Log::prn_log(INFO, "updated $this->affected_rows row");

        return true;
    }
}