<?php

/**
 * leveldb_server client lib.
 * send: <totallen><opt><keylen><key>[val]
 *   totallen: 4byte int
 *   opt:      1byte int
 *             0x00=set
 *             0x10=set with sync write
 *             0x01=get
 *             0x02=del
 *             0x12=del with sync write
 *             0x03=inc
 *             0x13=inc with sync write
 *             0x04=dec
 *             0x14=dec with sync write
 *             0xFF=other
 *   keylen:   1byte int
 *   key:      opt=0xFF: key=otp
 *             opt=0xXX: key=key
 *   val:      option
 *             opt=0xFF: val must give, it is param
 *             opt=0x00,0x10: val must give
 *             opt=0x01,0x02,0x12: val no give
 *             opt=0x03,0x13,0x04,0x14: val must give and is integer
 *
 * recv: <totallen><opt><val>
 *   opt: 1byte int
 *        0=success, 1=failed,
 *   val: result or error message.
 *
 * @author jiaofuyou@qq.com
 */

/**
 * leveldb_server client lib.
 * request/reponse is text, end with \r\n\r\n
 * send:
 *   set key val
 *   sy_set key val
 *   get key
 *   del key
 *   sy_del key
 *   inc key val
 *   sy_inc key val
 *   dec key val
 *   sy_dec key val
 *
 * recv:
 *   true value
 *   false errmsg
 *
 * @author jiaofuyou@qq.com
 */

/**
 * Log封装类
 * @author jiaofuyou@qq.com
 * @date   2014-11-25
 */

const ERROR    = 5;
const WARNING  = 4;
const NOTICE   = 3;
const INFO     = 2;
const DEBUG    = 1;
const TRACE     = 0;

class Log
{
    public static $log_level=NOTICE;

    public static function prn_log($level, $msg)
    {
        if ( $level>=self::$log_level )
            echo '['.posix_getpid().'.'.date("Y-m-d H:i:s").'] '.$msg."\n";
    }
}

/**
 * Leveldb client库封装类
 * @author jiaofuyou@qq.com
 * @date   2014-12-08
 */
class ldb_client
{
    private $socket = null;
    private $host;
    private $port;
    private $option;
    public $error;

    public function __construct($host,$port,$option=null)
    {
        $this->host = $host;
        $this->port = $port;
        $this->option = $option;
    }

    private function connect()
    {
        //创建 TCP/IP socket
        Log::prn_log(TRACE, 'socket connect ...');
        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if ($socket == false) {
          Log::prn_log(ERROR, 'socket_create failed: ' . socket_strerror(socket_last_error()));
          return false;
        } else {
          Log::prn_log(TRACE, 'socket create success!');
        }
        socket_set_option($socket,SOL_SOCKET,SO_RCVTIMEO,array("sec"=>5, "usec"=>0 ));
        socket_set_option($socket,SOL_SOCKET,SO_SNDTIMEO,array("sec"=>5, "usec"=>0 ));

        $result = @socket_connect($socket, $this->host, $this->port);
        if ($result == false) {
          Log::prn_log(ERROR, 'socket_connect failed: ' . socket_strerror(socket_last_error()));
          return false;
        } else {
          Log::prn_log(DEBUG, 'socket connect success!');
        }
        if ( $this->socket != null ) socket_close($this->socket);
        $this->socket = $socket;

        return true;
    }

    private function _send($data, $len)
    {
        $ret = socket_write($this->socket, $data, $len);
        if ( $ret === false ) {
            Log::prn_log(ERROR, 'socket_write failed: ' . socket_strerror($ret));
        }
        Log::prn_log(TRACE, 'socket_write ok!');

        Log::prn_log(TRACE, 'read socket ...');
        $out1 = socket_read($this->socket, 4);
        if ( $out1 === false ) {
            Log::prn_log(ERROR, 'socket_read failed: ' . socket_strerror($out1));
            return false;
        }
        $len = unpack('N', $out1);
        $out2 = socket_read($this->socket, $len[1]-4);
        if ( $out2 === false ) {
            Log::prn_log(ERROR, 'socket_read failed: ' . socket_strerror($out2));
            return false;
        }

        return $out1.$out2;
    }

    private function send($data, $len)
    {
        if ( $this->socket == null ) {
            if ( !$this->connect() ) return false;
        }

        Log::prn_log(DEBUG, '> '.strtoupper(bin2hex($data)));
        $out = $this->_send($data, $len);
        if ( $out === false ) {
            if ( !$this->connect() ) return false;
            $out = $this->_send($data, $len);
            if ( $out === false ) return false;
        }
        Log::prn_log(DEBUG, '< '.strtoupper(bin2hex($out)));

        return $out;
    }

    private function ldb_write($opt, $key, $val, $sync)
    {
        if ($sync) $opt=$opt|0x10;
        $keylen=strlen($key);
        $vallen=strlen($val);
        $len=4+1+1+$keylen+$vallen;
        $format_pack = 'NCCa'.$keylen.'a'.$vallen;
        $packdata = pack($format_pack, $len,$opt,$keylen,$key,$val);
        $out=$this->send($packdata, $len);
        if ( $out === false){
            $this->error = 'send error!';
            return false;
        }
        $format_unpack = 'Nlen/Cret/a*val';
        $data = unpack($format_unpack, $out);
        if ($data['ret']===0){
            $result = true;
            $this->error = '';
        } else {
            $result = false;
            $this->error = $data['val'];
        }

        return $result;
    }

    public function set($key, $val, $sync=false)
    {
        return $this->ldb_write(0x00, $key,$val,$sync);
    }

    public function del($key,$sync=false)
    {
        return $this->ldb_write(0x02, $key,'',$sync);
    }

    public function inc($key, $val, $sync=false)
    {
        return $this->ldb_write(0x03, $key,$val,$sync);
    }

    public function dec($key, $val, $sync=false)
    {
        return $this->ldb_write(0x04, $key,$val,$sync);
    }

    public function get($key)
    {
        $opt=0x01;
        $keylen=strlen($key);
        $len=4+1+1+$keylen;
        $format_pack = 'NCCa'.$keylen;
        $packdata = pack($format_pack, $len,$opt,$keylen,$key);
        $out=$this->send($packdata, $len);
        if ( $out === false){
            $this->error = 'send error!';
            return false;
        }
        $format_unpack = 'Nlen/Cret/a*val';
        $data = unpack($format_unpack, $out);
        if ($data['ret']===0){
            $result = $data['val'];
            $this->error = '';
        } else {
            $result = false;
            $this->error = $data['val'];
        }

        return $result;
    }

}
