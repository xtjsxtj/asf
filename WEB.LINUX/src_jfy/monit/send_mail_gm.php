<?php

/**
 * 发送邮件gearman调用接口
 * gearman函数名: sndmail
 * gearman数据包: json数组格式,如: ["jiaofuyou@qq.com", "邮件主题", "<h1>邮件内容(支持HTML格式)</h1>"]
 */

require_once('email.class.php');

# Create our worker object.
$gmworker= new GearmanWorker();

# Add default server (localhost).
$gmworker->addServer("127.0.0.1", 4730);

# Register function "reverse" with the server. Change the worker function to
# "reverse_fn_fast" for a faster worker with no output.
$gmworker->addFunction("sndmail", "sndmail");

echo '['.posix_getpid().'.'.date("Y-m-d H:i:s").'] ' . "Waiting for job ...\n";
while($gmworker->work())
{
  if ($gmworker->returnCode() != GEARMAN_SUCCESS)
  {
    echo '['.posix_getpid().'.'.date("Y-m-d H:i:s").'] ' . "return_code: " . $gmworker->returnCode() . "\n";
    break;
  }
}

function sndmail($job)
{
    $workload= $job->workload();
    $workload_size= $job->workloadSize();

    echo '['.posix_getpid().'.'.date("Y-m-d H:i:s").']'." < " . $workload . "\n";
    $json_req = json_decode($workload, true);
    $json_req[1] = urldecode($json_req[1]);
    $json_req[2] = urldecode($json_req[2]);

    //##########################################
    $smtpserver = "smtp.qq.com";//SMTP服务器
    $smtpserverport = 25;//SMTP服务器端口
    $smtpusermail = "elitelmonit@qq.com";//SMTP服务器的用户邮箱
    $smtpuser = "elitelmonit";//SMTP服务器的用户帐号
    $smtppass = "xtjsxtj302111";//SMTP服务器的用户密码
    $smtpemailto = $json_req[0];//发送给谁
    $mailsubject = $json_req[1];//邮件主题
    $mailbody = $json_req[2];//邮件内容
    $mailtype = "HTML";//邮件格式（HTML/TXT）,TXT为文本邮件
    ##########################################

    $smtp = new smtp($smtpserver,$smtpserverport,true,$smtpuser,$smtppass);//这里面的一个true是表示使用身份验证,否则不使用身份验证.
    $smtp->debug = false;//是否显示发送的调试信息

    $smtp->sendmail($smtpemailto, $smtpusermail, $mailsubject, $mailbody, $mailtype);
    echo '['.posix_getpid().'.'.date("Y-m-d H:i:s").']'." > " . 'sendmail ok' . "\n";

    $result= "";

    # Return what we want to send back to the client.
    return $result;
}
