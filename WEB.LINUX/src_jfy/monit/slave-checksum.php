<?php

/*
./mysqldump --single-transaction --databases 3alogic boss cbvacs elitel_access wollar wxqyh --master-data=1 | mysql --host=172.16.18.116

CREATE TABLE `dsns` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) DEFAULT NULL,
  `dsn` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
);
INSERT INTO dsns (parent_id,dsn) values(1,'h=172.16.18.116,u=root,p=cpyf,P=3306');
INSERT INTO dsns (parent_id,dsn) values(1,'h=172.16.18.165,u=root,p=cpyf,P=3306');

pt-table-checksum --nocheck-replication-filters --databases 3alogic --recursion-method=dsn=h=172.16.18.114,D=test,t=dsns
pt-table-checksum --nocheck-replication-filters --databases 3alogic --replicate percona.checksums --create-replicate-table --no-check-binlog-format --replicate-check-only

pt-table-sync --print --replicate=percona.checksums --databases=3alogic --ignore-tables=3alogic.log,3alogic.peap_surf_record h=localhost h=58.64.142.44
pt-table-sync --execute --replicate=percona.checksums --databases=3alogic --ignore-tables=3alogic.log,3alogic.peap_surf_record h=localhost h=58.64.142.44
*/

$checksum_database_list=array('3alogic','boss','elitel_access','cbvacs', 'wollar');

$mobile = '13189149999';
$email  = 'jiaofuyou@qq.com';
$to     = 'mail,wqy'; //all,sms,mail,wqy

$cmd_alert = "/usr/bin/php /usr1/app/php/send_alert.php {$to} {$mobile} {$email}";

$desc = '';
echo '['.date('Y-m-d H:i:s').']'."slave checksum ...\n\n";
foreach($checksum_database_list as $database){
    echo "table-checksum [$database] ...\n";
    $cmd = "/usr/local/mysql/bin/pt-table-checksum --nocheck-replication-filters --databases $database --no-check-binlog-format --recursion-method=dsn=h=172.16.18.114,D=test,t=dsns";
    $out = null;
    exec($cmd, $out);
    $cmd = "/usr/local/mysql/bin/pt-table-checksum --nocheck-replication-filters --databases $database --no-check-binlog-format --replicate-check-only --recursion-method=dsn=h=172.16.18.114,D=test,t=dsns";
    $out = null;
    exec($cmd, $out);
    foreach($out as $line) echo "$line\n";
    if ( count($out) > 0 ) {
        $desc .= $database.',';
        echo "\n";
    }
}

{    
    echo "table-checksum only 3alogic ...\n";
    $cmd = "/usr/local/mysql/bin/pt-table-checksum --nocheck-replication-filters --databases 3alogic --ignore-tables=3alogic.log,3alogic.peap_surf_record,3alogic.peapuser_mac --no-check-binlog-format";
    $out = null;
    exec($cmd, $out);
    $cmd = "/usr/local/mysql/bin/pt-table-checksum --nocheck-replication-filters --databases 3alogic --ignore-tables=3alogic.log,3alogic.peap_surf_record,3alogic.peapuser_mac --no-check-binlog-format --replicate-check-only";
    $out = null;
    exec($cmd, $out);
    foreach($out as $line) echo "$line\n";
    if ( count($out) > 0 ) {
        $desc .= '3alogic'.',';
        echo "\n";
    }
}
    
if ( $desc != '' ) {
    putenv("MONIT_EVENT=mysql slave table-checksum");
    putenv("MONIT_SERVICE=slave");
    putenv("MONIT_DATE=".date('Y-m-d H:i:s'));
    putenv("MONIT_HOST=172.16.18.114");
    putenv("MONIT_DESCRIPTION=table-checksum [$desc] diffrent");
    echo "diffrent: $desc send alert ...\n";
    exec($cmd_alert);
} else {
    echo "\ntable-checksum no problem\n\n";
}
