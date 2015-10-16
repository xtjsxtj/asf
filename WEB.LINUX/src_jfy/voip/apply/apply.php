<?php

function test($job)
{
    $job=new job('voip_kf', '{"msisdn":"85265101177","calltype":"c","callid":"85265101177_85265101177_2014-11-21 12:11:34_1140","duration":"60","amount":"0.01","callnob":"85262506125","callnoa":"85265101177"}');
    return apply_with_common($job);
}

function create_user($req)
{
    global $db;
    define('DIRECTEL_PLMN', 0x7E);//01111110
    define('HK_OTHER_LOCAL_OPT', 0x66);//01100110
    $ret['return']=false;
    $ret['result']=encode_json(array('errorCode' => '552'));

    if ( !check_array($req,array('username', 'operatorId', 'regMsisdn', 'simInfo')) ) return $ret;
    $msisdn = $req['regMsisdn'];
    if ((strlen($msisdn)==11)&&(substr($msisdn,0,3)=='852')) $ccode='852';
    else if ((strlen($msisdn)==13)&&(substr($msisdn,0,2)=='86')) $ccode = '86';
    else {
        Log::prn_log(ERROR, 'msisdn must is 852XXXXXXXX(11 num) or 86XXXXXXXXXXX(13 num)!');
        return $ret;
    }

    if ( chk_user_valid(array('msisdn'=>$req['regMsisdn']), $user) === true ) {
        $rep=array(
            "smsVerificationFlag" => $user['zgtflag']=='Y'?false:true,
            "userType" => $user['usertype'],
            "featureClass" => $user['zgtflag']=='Y'?DIRECTEL_PLMN:HK_OTHER_LOCAL_OPT,
            "username" => $user['userid'],
            "alternateMsisdnList" => array(array(
                "msisdn" => $req['regMsisdn'],
                "countryCode" => $ccode,
                "isHPLMNNumber" => $user['zgtflag']=='Y'?true:false,
                "imsi" => $user['siminfo'],
            )),
            "state" => "ACTIVE",
            "errorCode" => "000",
        );
        if ( $user['siminfo'] != $req['simInfo'] ) $rep['smsVerificationFlag']=true;

        return assign_return(true, encode_json($rep));
    }

    $retu=get_msisdn_info($req['regMsisdn'],$msisdn);
    if ( $retu === -1 ) return $ret;
    if ( $retu === false ) {
        if ($ccode == '852') {
            $msisdn=array('msisdn'=>$req['regMsisdn'],'zgtflag'=>false,'imsi'=>'','usertype'=>'HK_OLO');
        } else {
            $msisdn=array('msisdn'=>$req['regMsisdn'],'zgtflag'=>false,'imsi'=>'','usertype'=>'CN_OLO');
        }
    } else {
        if ($ccode == '86') $msisdn['usertype'] = 'CN_CU';
    }
    if ($msisdn['zgtflag']) {
        //$feature='2,4,8,16,32,64';
        $feature = '01111110';
        $featureClass=DIRECTEL_PLMN;
        $smsflag=false;
    } else {
        //$feature='2,4,32,64';
        $feature='01100110';
        $featureClass=HK_OTHER_LOCAL_OPT;
        $smsflag=true;
    }
    if ( $msisdn['imsi'] != $req['simInfo'] ) $smsflag=true;

    $userid=rand(10000000,99999999);
    $ret['keykey']=$userid;

    $sqlstr=sprintf(" insert into user (userid,username,feature,secure,siminfo,msisdn, "
                   ." countyrcode,zgtflag,usertype,operatorid,createtime) "
                   ." values ('%s','%s','%s',%d,'%s','%s','%s','%s','%s','%s',now())",
           $userid,$req['username'],$feature,0,$msisdn['imsi'],$msisdn['msisdn'],$ccode,$msisdn['zgtflag']?'Y':'N',
           $msisdn['usertype'],$req['operatorId']);
    if ( !$db->insert_one($sqlstr)) return $ret;
    if ( !$msisdn['zgtflag'] ) {
        $sqlstr="insert into useracnt values('{$userid}',now(),0.00,'2099-12-31','A','2099-12-31')";
        if ( !$db->insert_one($sqlstr)) return $ret;
    }

    $rep=array(
        "smsVerificationFlag" => $smsflag,
        "userType" => $msisdn['usertype'],
        "featureClass" => $featureClass,
        "username" => $userid,
        "alternateMsisdnList" => array(array(
            "msisdn" => $req['regMsisdn'],
            "countryCode" => $ccode,
            "isHPLMNNumber" => $msisdn['zgtflag'],
            "imsi" => $msisdn['imsi'],
        )),
        "state" => "ACTIVE",
        "errorCode" => "000",
    );

    return assign_return(true, encode_json($rep));
}

function set_secure($req)
{
    global $db;
    $ret['return']=false;
    $ret['result']=encode_json(array('errorCode' => '552'));

    if ( !check_array($req,array('username', 'operatorId', 'regMsisdn', 'simInfo', 'sSipStatus')) ) return $ret;

    //$msisdn = $req['regMsisdn'];
    //if ( chk_user_valid(array('msisdn'=>$req['regMsisdn']), $user) !== true ) return $ret;
    if ( chk_user_valid(array('userid'=>$req['username']), $user) !== true ) {
        $ret['keykey'] = "err: account.userid({$req['username']}) not found!";
        return $ret;
    }
    if ( $user['msisdn'] != $req['regMsisdn'] ) {
        Log::prn_log(ERROR, "user.msisdn({$user['msisdn']}) <> req.regMsisdn({$req['regMsisdn']})!");
        $ret['keykey'] = "err: user.msisdn({$user['msisdn']}) <> req.regMsisdn({$req['regMsisdn']}!";
        return $ret;
    }
    
    $secure=$req['sSipStatus']==true?1:0;
    $sqlstr="update user set secure={$secure} where msisdn='{$user['msisdn']}'";
    if ( $db->update_more($sqlstr) !== true ) return $ret;

    $rep=array(
        "errorCode" => "000",
    );
    return assign_return(true, encode_json($rep));
}

function get_user($req)
{
    global $db;

    if ( chk_user_valid($req, $user) !== true ) return assign_error('MMM', $user);

    if ( $user['zgtflag'] === 'Y' ) {
        if ( get_msisdn_info($user['msisdn'], $useracnt) !== true ) return assign_error('MMM', $useracnt);
    } else {
        $useracnt=$db->select_one("select * from useracnt where userid='{$user['userid']}'");
        if ( $useracnt === false ){
            return assign_error('MMM', 'userid:'.$req['userid'].' useracnt is not found!');
        }
        $useracnt['amount'] = $useracnt['amount']/100.00;
    }

    $user['score']=500;
    return assign_ok('000',$user+$useracnt);
}

function recharge($req)
{
    global $db;

    if ( chk_user_valid($req, $user) !== true ) return assign_error('MMM', $user);
    $ret['keykey']=$user['userid'];

    if ( !isset($req['cardno']) ) {
        Log::prn_log(ERROR, 'query param is error,<cardno> is not exists!');
        return assign_error('MMM', 'query param is error!');
    }

    if ( $user['zgtflag'] === 'Y' ) {
        if ( apply_recharge($user['msisdn'],$req['cardno'],$result) !== true ) return assign_error('MMM', $result);
    } else {
        if ( check_recharge($req['cardno'],$result) !== true ) return assign_error('MMM', $result);
        $amount=round($result['amount']*100);
        $sqlstr="update useracnt set amount=amount+{$amount} where userid='{$user['userid']}'";
        if ( $db->update_one($sqlstr) !== true ) {
            return assign_error('MMM', 'update useracnt failed,'.$user['userid']);
        }
        if ( update_recharge($user['msisdn'], $req['cardno'],$result) !== true ) return assign_error('MMM', $result);
    }

    $sqlstr="insert into rechargelog values(0,'{$user['userid']}','CZJ','{$req['cardno']}',{$result['amount']},now(),0,'A','OK')";
    if ( $db->insert_one($sqlstr) !== true ) {
        return assign_error('MMM', 'insert rechargelog failed');
    }

    return assign_ok('000', 'OK');
}
