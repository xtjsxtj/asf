<?php

class job
{
    public $funcname;
    public $data;

    public function __construct($funcname,$data)
    {
        $this->funcname=$funcname;
        $this->data=$data;
    }

    function workload()
    {
        return $this->data;
    }

    function functionName()
    {
        return $this->funcname;
    }
}

/**
 * 将返回码与返回参数json_encode
 * @param string $errcode M01/M02/MMM
 * @param string/array $msg
 * @return string json字符串,{'error_code'=>'000', 'error_msg'=>'XXX', ...}
 */
function assign_result($errcode, $msg)
{
    if ( is_array($msg) ) {
        $result=array('error_code'=>$errcode)+$msg;
    } else {
        $result['error_code']=$errcode;
        $result['error_msg']=$msg;
    }

    return encode_json($result);
}

/**
 * 返回的数组结果
 * @param boolean $return
 * @param string $msg
 * @return array('return'=>true/false, 'result'=>XXXX)
 */
function assign_return($return, $msg)
{
    $ret['return']=$return;
    $ret['result']=$msg;

    return $ret;
}

/**
 * 返回数组结果false
 * @param string $errcode
 * @param string $msg
 * @return array('return'=>false, 'result'=>XXXX)
 */
function assign_error($errcode, $msg)
{
    return assign_return(false, assign_result($errcode, $msg));
}

/**
 * 返回数组结果true
 * @param string $errcode
 * @param string $msg
 * @return array('return'=>true, 'result'=>XXXX)
 */
function assign_ok($errcode, $msg)
{
    return assign_return(true, assign_result($errcode, $msg));
}

/**
 * 合法性检查（暂未生效）
 * @param type $id
 * @param type $token
 * @return boolean
 */
function check_valid($id, $token)
{
    return true;
}

/**
 * 向mysql写操作日志
 * @global type $db
 * @param string $funcname 调用函数名
 * @param string $keykey 操作关键信息，如：号码，userid
 * @param string $jsonreq json请求参数
 * @param string $jsonrep json应答参数
 * @return boolean true/false
 */
function write_oplog($funcname, $keykey, $jsonreq, $jsonrep)
{
    global $db;
    return $db->insert_one("insert into oplog values(0, now(),
                           '$funcname', '$keykey', '$jsonreq', '$jsonrep')",false);
}

/**
 * 普通函数调用预处理，会先将$job参数解析成array
 * @param job $job
 * @return array=('return'=>true/false,'result'=>'XXX','keykey'=>'XXX',...}
 */
function apply_with_common($job)
{
    $request=$job->workload();
    $funcname=$job->functionName();
    Log::prn_log(NOTICE, 'request:'.$funcname.':'.$request);

    $req=json_decode($request, true);
    if ( $req == NULL ) {
        Log::prn_log(ERROR, 'param error, json_decode is NULL!');
        return assign_result('MMM', 'param error!');
    }
    $ret=$funcname($req);
    Log::prn_log(NOTICE, 'result:'.$ret['result']);

    return $ret['result'];
}

/**
 * 写日志类函数调用预处理，会先将$job参数解析成array，调用结束后会写oplog
 * @param job $job
 * @return array=('return'=>true/false,'result'=>'XXX','keykey'=>'XXX',...}
 */
function apply_with_log($job)
{
    $request=$job->workload();
    $funcname=$job->functionName();
    Log::prn_log(NOTICE, 'request:'.$funcname.':'.$request);

    $req=json_decode($request, true);
    if ( $req == NULL ) {
        Log::prn_log(ERROR, 'param error, json_decode is NULL!');
        return assign_result('MMM', 'param error!');
    }
    $ret=$funcname($req);
    Log::prn_log(NOTICE, 'result:'.$ret['result']);

    if ( !isset($ret['keykey'] ) ) $ret['keykey']='';
    write_oplog($funcname, $ret['keykey'], $request, $ret['result']);

    return $ret['result'];
}

/**
 * 事务类函数调用预处理，会先将$job参数解析成array，将起事务，调用结束后会写oplog，成功提交事务，失败回滚事务
 * @param job $job
 * @return array=('return'=>true/false,'result'=>'XXX','keykey'=>'XXX',...}
 */
function apply_with_tran($job)
{
    global $db;

    $request=$job->workload();
    $funcname=$job->functionName();
    Log::prn_log(NOTICE, 'request:'.$funcname.':'.$request);

    $req=json_decode($request, true);
    if ( $req == NULL ) {
        Log::prn_log(ERROR, 'param error, json_decode is NULL!');
        return assign_result('MMM', 'param error!');
    }

    //$db->autocommit(FALSE);
    $db->query('SET AUTOCOMMIT=0'); //支持mysql自动重连
    $ret=$funcname($req);
    Log::prn_log(NOTICE, "result:".$ret['result']);
    if ($ret['return']) {
      $db->commit();
    } else {
      $db->rollback();
    }
    $db->autocommit(TRUE);
    if ( !isset($ret['keykey'] ) ) $ret['keykey']='';
    write_oplog($funcname, $ret['keykey'], $request, $ret['result']); 
              
    return $ret['result'];
}

/**
 * 根据msisdn/userid检查用户合法性
 * @global mysqldb $db
 * @param array $req 请求参数{'userid'=>'XXX', 'msisdn'=>'XXX'} 两个参数必须有其一
 * @param array/string $user 用户资料数组/错误信息描述
 * @return boolean
 */
function chk_user_valid($req, &$user)
{
    global $db;

    if ( isset($req['userid']) ) {
        $user=$db->select_one("select * from user where userid='{$req['userid']}'");
        if ( $user === false ) {
          $user='accounter is not exists!';
          return false;
        }
    } else
    if ( isset($req['msisdn']) ) {
        $user=$db->select_one("select * from user where msisdn='{$req['msisdn']}'");
        if ( $user === false ) {
          $user='accounter is not exists!';
          return false;
        }
    } else {
        Log::prn_log(ERROR, 'query param is error,<userid> or <msisdn> is not exists!');
        $user='query param is error!';
        return -1;
    }

    return true;
}

/**
 * 号码格式化
 * @param string $msisdn 香港号码（带各种可能前缀）
 * @return string 格式化后的号码（852XXXXXXXX）
 */
function format_msisdn($msisdn)
{
    if ((substr($msisdn,0,2) == '86')&&(strlen($msisdn)==13)) return $msisdn;
    else if ((substr($msisdn,0,3) == '+86')&&(strlen($msisdn)==14)) return substr($msisdn,1);
    else if ((substr($msisdn,0,3) == '852')&&(strlen($msisdn)==11)) return $msisdn;
    else if ((substr($msisdn,0,4) == '+852')&&(strlen($msisdn)==12)) return substr($msisdn,1);
    else if ((substr($msisdn,0,4) == '4920')&&(strlen($msisdn)==12)) return '852'.substr($msisdn,4);
    else if ((substr($msisdn,0,5) == '00852')&&(strlen($msisdn)==13)) return substr($msisdn,2);
    else if (strlen($msisdn)==8) return '852'.$msisdn;
    else return $msisdn;
}
