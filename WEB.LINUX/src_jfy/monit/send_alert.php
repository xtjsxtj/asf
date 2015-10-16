<?php

$gmclient= new GearmanClient();
$gmclient->addServer();
$gmclient->setTimeout(10000);

function sndsms($mobile,$msg)
{
    global $gmclient;
    $CmdStr[0] = $mobile;  //手机号码
    $CmdStr[1] = urlencode($msg);  //告警信息内容
    $jsonstr = urldecode(json_encode($CmdStr));
    $gmclient->doBackground('sndsms', $jsonstr);
    error_log(date("[Y-m-d H:i:s]")." > "."sndsms: $jsonstr\n", 3 , "/usr1/app/log/send_alert.log");
}

function sndmail($email,$subject,$content)
{
    global $gmclient;
    $CmdStr[0] = $email; //接收告警邮箱地址
    $CmdStr[1] = urlencode($subject); //邮件主题
    $CmdStr[2] = urlencode($content); //邮件内容(支持HTML格式)
    $jsonstr = json_encode($CmdStr);
    $gmclient->doBackground('sndmail', $jsonstr);
    error_log(date("[Y-m-d H:i:s]")." > "."sndmail: $jsonstr\n", 3 , "/usr1/app/logs/send_alert.log");
}

function sndwqy($mobile,$msg)    //微信企业号告警信息
{
    global $gmclient;
    $CmdStr[0] = explode(',', $mobile);  //手机号码
    $CmdStr[1] = urlencode($msg);  //告警信息内容
    $jsonstr = urldecode(json_encode($CmdStr));
    $gmclient->doBackground('wx_sendmsg', $jsonstr);
    error_log(date("[Y-m-d H:i:s]")." > "."sndwqy: $jsonstr\n", 3 , "/usr1/app/logs/send_alert.log");
}

$env['EVENT']       = getenv('MONIT_EVENT');
$env['DESCRIPTION'] = getenv('MONIT_DESCRIPTION');
$env['SERVICE']     = getenv('MONIT_SERVICE');
$env['DATE']        = getenv('MONIT_DATE');
$env['HOST']        = getenv('MONIT_HOST');
if ($argc>=5) $env['DESCRIPTION'] = $argv[4];
else $argv[4]='';
$param = sprintf("send_alert %s %s %s %s", $argv[1],$argv[2],$argv[3],$argv[4]);

error_log(date("[Y-m-d H:i:s]")." > ".$param."\n", 3 , "/usr1/app/logs/send_alert.log");

$msg = "{$env['SERVICE']}, {$env['DESCRIPTION']}.";
$text    = "{$env['EVENT']}\\n\\n"
         . "{$env['DATE']}\\n"
         . "{$env['SERVICE']}\\n"
         . "{$env['DESCRIPTION']}";
$subject = "monit alert --  {$env['EVENT']} {$env['SERVICE']}";
$content = "{$env['EVENT']} Service {$env['SERVICE']}    <br>"
         . "      Date:        {$env['DATE']}            <br>"
         . "      Host:        {$env['HOST']}            <br>"
         . "      Description: {$env['DESCRIPTION']}     <br>"
         . "                                             <br>"
         . " elitel moniter                              <br>";

$mobile = $argv[2];
$email = $argv[3];
$tos = explode(',', $argv[1]);

foreach($tos as $to) {
  if ( $to == 'sms' ) {
      $mobiles = explode(',', $mobile);
      foreach($mobiles as $moeileone) sndsms($moeileone,$msg);
  }

  if ( $to == 'mail' ) {
      sndmail($email,$subject,$content);
  }

  if ( $to == 'wqy' ) {
      sndwqy($mobile,$text);
  }

  if ( $to == 'all' ) {
      $mobiles = explode(',', $mobile);
      foreach($mobiles as $moeileone) sndsms($moeileone,$msg);
      sndmail($email,$subject,$content);
      sndwqy($mobile,$text);
  }
}
