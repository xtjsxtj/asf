<?php

function to_yyyyy_mm_dd($yyyymmdd)
{
    return substr($yyyymmdd, 0, 4) . '-' . substr($yyyymmdd, 4, 2) . '-' . substr($yyyymmdd, 6, 2);
}

function incdate($yyyymmdd, $days)
{
    $yyyy_mm_dd = to_yyyyy_mm_dd($yyyymmdd);
    return date('Ymd', strtotime($days.' day', strtotime($yyyy_mm_dd)));
}

function month_start_end($month, &$startdate, &$enddate)
{
    $startdate = $month . '01';
    $days = date('t', strtotime(to_yyyyy_mm_dd($startdate)));
    $enddate = sprintf('%s%02d', $month, $days);
}

function callno_get($msisdn_list,$date)
{
    global $db;
    
    $msisdn_list_result = array();
    foreach($msisdn_list as $msisdn) { 
        if ( (substr($msisdn,0,3) == '852') && (strlen($msisdn)==11) ) $callno = substr($msisdn, 3);
        if ( (substr($msisdn,0,5) == '85300') && (strlen($msisdn)==13) ) $callno = substr($msisdn, 5);
        $sqlstr = "select bossid,permark,vestss from euserst where uid=("
                . "select max(uid) from euserst where callno = '$callno' and opendate<='$date')";
        if ( ($result=$db->select_one($sqlstr,false)) !== false ) {
            $sqlstr = "select permark from subpp where bossid='{$result['bossid']}'";
            if ( ($subpp=$db->select_one($sqlstr,false)) === false  ) {
                $result['subpp'] = '';
            } else {
                $result['subpp'] = $subpp['permark'];
            }
        } else {
            $result['vestss'] = ' ';
            $result['bossid'] = ' ';
            $result['permark'] = ' ';
            $result['subpp'] = ' '; 
        }
        $msisdn_list_result[$msisdn] = $result;
    }
    
    return $msisdn_list_result;
}
