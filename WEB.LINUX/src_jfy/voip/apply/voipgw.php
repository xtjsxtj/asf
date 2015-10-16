<?php

function voip_jq($req)
{
    global $db;
    $rate=array(
        '1606' => 160,
        '001' => 161,
        'LOCALMO' => 162,
        'MT' => 163,
    );

    if ( chk_user_valid($req, $user) !== true ) {
        return assign_error('M01', 'account is not exists');
    }

    if ( !isset($req['precode']) ) {
        Log::prn_log(ERROR, 'query param is error,<precode> is not exists!');
        return assign_error('M03', 'query param is error!');
    }
    if ( $user['zgtflag'] === 'Y' ) {
        if ( get_msisdn_info($user['msisdn'], $useracnt, $req['precode']) !== true ) return assign_error('M03', $useracnt);
        return assign_ok('000', array('rate_code'=>$useracnt['rate_code']));
    } else {
        if (!isset($rate[$req['precode']])) $rate[$req['precode']]='000';
        return assign_ok('000', array('rate_code'=>$rate[$req['precode']]));
    }
}

function voip_kf($req)   //10000qps
{
    global $db;
    $status='000';
    $memo='OK';

    if ( !check_array($req,array('msisdn','callnoa','callnob','amount','duration','callid','calltype')) ) {
        return assign_error('M03', 'data format error!');
    }

    if ( chk_user_valid($req, $user) !== true ) {
        return assign_error('M02', 'account is not exists');
    }
    if ( intval($user['secure']) !== 0 ) $req['amount']=$req['amount']+0.5;
    
    if ( $user['zgtflag'] === 'Y' ) {
        $req['amount'] = sprintf("%.2f", $req['amount']);
        $req['secure'] = $user['secure'];
        if ( apply_charging($req,$result) !== true ) {
            $status='M03';
            $memo=$result;
        }
    } else {
//        $useracnt=$db->select_one("select * from useracnt where userid='{$user['userid']}'");
//        if ( $useracnt === false ){
//            $status='M02';
//            $memo='userid:'.$req['userid'].' useracnt is not found!';
//        } else
//        if ( $useracnt['amount'] < $req['amount'] ) {
//            $status='M01';
//            $memo='balance is not enough!';
//        } else {
            $amount=round($req['amount']*100);            
            $sqlstr="update useracnt set amount=amount-{$amount} where userid='{$user['userid']}'";
            if ( $db->update_one($sqlstr) !== true ) {
                if ( $db->errno == 1690 ) {
                    $status='M01';
                    $memo='balance is not enough!';
                } else {
                    $status='M03';
                    $memo='system error!';
                }
            }
//        }
    }

    $sqlstr ="insert into charginglog values(0,'{$user['userid']}','{$user['msisdn']}','{$req['callnoa']}','{$req['callnob']}',";
    $sqlstr.="'{$req['calltype']}',{$req['amount']},{$req['duration']},'{$req['callid']}',now(),'{$status}','{$memo}')";
    if ( $db->insert_one($sqlstr) !== true ) {
        $status='M03';
        $memo='system error!';
    }

    $ret=assign_ok($status, $memo);
    $ret['keykey']=$req['msisdn'];

    return $ret;
}

function voip_kf_async($req)  //15000qps,todo封装handlersocket解决断开重连
{
    global $hs_read;
    global $hs_write;
    global $idx_user_r;
    global $idx_useracnt_w;

    if (!isset($hs_read)) {
        try
        {
            $hs_read = new HandlerSocket('localhost', 9998);
            $idx_user_r = $hs_read->createIndex(1, 'voip', 'user', 'PRIMARY', array('userid','zgtflag'));

            $hs_write = new HandlerSocket('localhost', 9999);
            $idx_useracnt_w = $hs_write->createIndex(3, 'voip', 'useracnt', 'PRIMARY', 'amount');

        }
        catch (HandlerSocketException $exception)
        {
            Log::prn_log(ERROR, $exception->getMessage());
            return assign_error('MMM', $exception->getMessage());
        }
        Log::prn_log(INFO , 'handlersockdt conn ok!');
    }

    //GET
    $retval = $idx_user_r->find('85265101177');
    if ( $retval === false)
    {
        Log::prn_log(ERROR, $idx_user_r->getError());
        return assign_error('MMM', 'find user error!');
    }
    if ( !is_array($retval) ) {
        //如果不是数组说明没有更新到记录
        Log::prn_log(ERROR, "find not found!");
        return assign_error('M01', 'account is not exists');
    }
    list($userid,$zgtflag) = $retval[0];

    //UPDATE
    $amount=round(0.01*100);
    $retval = $idx_useracnt_w->update($userid, array('-?' => $amount));   //'-?'会返回更新前的值
    if ( $retval === false)
    {
        Log::prn_log(ERROR, $idx_useracnt_w->getError());
        return assign_error('MMM', 'update useracnt error!');
    }
    if ( !is_array($retval) ) {
        //如果不是数组说明没有更新到记录
        Log::prn_log(ERROR, 'update not found!');
        return assign_error('MMM', 'update useracnt error, not found!');
    }
    //是数组判断原余额够不够
    if ( intval($retval[0][0]) < $amount ) {
        Log::prn_log(ERROR, 'balance is not enough!');
        return assign_error('M01', 'balance is not enough!');
    }

    return assign_ok('000', 'ok!');
}
