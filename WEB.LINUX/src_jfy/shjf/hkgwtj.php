<?php

/**
 * 统计香港国联话单
 * partigroupname  = HGCIN,HGCIN2,HGCIN3,GZCallCenter,GZCallCenter3'
 * partiigroupname = GMSC
 */

require_once dirname(__FILE__).'/./pub.php';

if ( $argc > 3 )
{
    echo '-- hkgwtj ' . "\n";
    echo '-- hkgwtj <yyyymm>' . "\n";
    echo '-- hkgwtj <startdate> <enddate>' . "\n";
    exit;
}

if ( $argc == 1 )
{
    global $startdate;
    global $enddate;
    $yyyymm = date('Ym', strtotime('-1 month'));
    month_start_end($yyyymm, $startdate, $enddate);
}
if ( $argc == 2 )
{
    global $startdate;
    global $enddate;
    $yyyymm = $argv[1];
    month_start_end($yyyymm, $startdate, $enddate);
}
if ( $argc == 3 )
{
    global $startdate;
    global $enddate;
    $startdate = $argv[1];
    $enddate = $argv[2];
}

$path = '/usr1/data/cdr_hkgw';
//$path = '/home/jfy/tmp/cdr_hkgw/';

function proc_calltime($partiitime1, $partiitime4, &$calldate, &$calltime, &$calldura)
{
  $time = strtotime($partiitime1);
  $calldate = date('Ymd', $time);
  $calltime = date('hms', $time);
  $calldura = strtotime($partiitime4) - $time;
}

$outfile= '/usr1/data/cdr_hkgw/HKGWTJ_'.date('Ymd').'.TXT';
file_put_contents($outfile, '');

for($curdate=$startdate;$curdate<=$enddate;$curdate=incdate($curdate,1))
{
    $yearmon = substr($curdate,0,6);
    $dir = "$path/$yearmon/$curdate/";
    echo $dir . " ...\n";

    $dh = @opendir($dir);
    if ( $dh === false ) {
        echo "open $dir error!\n";
        exit;
    }

    $files = '';
    while (($file = readdir($dh)) !== false)
    {
        if ( $file == '.' || $file == '..' ) continue;
        $files[] = $dir.$file;
    }
    asort($files);
    foreach($files as $file)
    {
        //echo $file . "\n";
        $file_content = explode("\r\n", file_get_contents($file));
        $out = '';
        foreach($file_content as $line_str)
        {
            if ( $line_str == '' ) continue;
            //echo $line_str . "\n";
            list($line['serial'],$line['apptype'],$line['usertype'],$line['useraccount'],
                 $line['userinfo'],$line['calltype'],$line['callstatus'],$line['particonntype'],
                 $line['particonnstatus'],$line['partigroupname'],$line['partispan'],
                 $line['partichannel'],$line['partiaddress1'],$line['partiaddrees2'],
                 $line['partiaddress3'],$line['partitime1'],$line['partitime2'],$line['partitime3'],
                 $line['partitime4'],$line['partiiconntype'],$line['partiiconnstatus'],
                 $line['partiigroupname'],$line['partiispan'],$line['partiichannel'],
                 $line['partiiaddress1'],$line['partiiaddrees2'],$line['partiiaddress3'],
                 $line['partiitime1'],$line['partiitime2'],$line['partiitime3'],
                 $line['partiitime4'],$line['srcmtccall']) = explode(',', $line_str);
            if ( $line['partiigroupname'] == 'NWT009LTS' ) {
              if ( substr($line['partiitime2'],0,10) == '1970-01-01' ) {
                //echo "该次通话YNEa29=1970-01-01，没有接通,不处理!\n";
                continue;
              }
              proc_calltime($line['partiitime2'], $line['partiitime4'], $line['calldate'], $line['calltime'], $line['calldura']);
            } else {
              if ( substr($line['partiitime1'],0,10) == '1970-01-01' ) {
                //echo "该次通话YNEa28=1970-01-01，没有接通,不处理!\n";
                continue;
              }
              proc_calltime($line['partiitime1'], $line['partiitime4'], $line['calldate'], $line['calltime'], $line['calldura']);
            }
            //echo "{$line['partigroupname']},{$line['partiigroupname']},{$line['partiitime1']},{$line['partiitime2']},{$line['partiitime4']},{$line['calldate']}-{$line['calltime']},{$line['calldura']}\n";
            if ( (strpos('HGCIN,HGCIN2,HGCIN3,GZCallCenter,GZCallCenter3', $line['partigroupname']) !== false) &&
                 ($line['partiigroupname'] == 'GMSC') ) {
                //echo "{$line['partigroupname']},{$line['partiigroupname']},{$line['partiaddress1']},"
                //    ."{$line['partiaddress3']},{$line['partitime1']},{$line['partitime4']},{$line['calldura']}\n";
                $out .= "{$line['partigroupname']},{$line['partiigroupname']},{$line['partiaddress1']},"
                      . "{$line['partiaddress3']},{$line['partitime1']},{$line['partitime4']},{$line['calldura']}\r\n";
            }
        }
        file_put_contents($outfile, $out, FILE_APPEND);
    }
    closedir($dh);
}

echo 'end!'."\n";
