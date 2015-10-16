#!/bin/sh

month=`date +%m`
day=`date +%d`
year=`date +%Y`
month=`expr $month + 0`
day=`expr $day + 0`
month=`printf "%02d" $month"`
day=`printf "%02d" $day"`
yyyymmdd=$year$month$day

echo "$yyyymmdd ..."

# 164 DB data
data_path=/usr1/data

echo "uncompress data ..."
uncompress -f $data_path/euserst.txt.Z
uncompress -f $data_path/subpp.txt.Z

echo "delete from euserst ..."
/usr/local/mysql/bin/mysql shjf -e "delete from euserst"
echo "mysqlimport euserst.txt ..."
/usr/local/mysql/bin/mysqlimport --fields-terminated-by='|' shjf $data_path/euserst.txt

echo "delete from subpp ..."
/usr/local/mysql/bin/mysql shjf -e "delete from subpp"
echo "mysqlimport subpp.txt ..."
/usr/local/mysql/bin/mysqlimport --fields-terminated-by='|' shjf $data_path/subpp.txt
