create database shjf;
use shjf;

drop table if exists ggsncdr;
create table ggsncdr (
    recid        integer         not     null auto_increment,
    timestamp    integer         not     null,
    datavol      bigint          not     null,
    duration     integer         not     null,
    pdpadd       char(15)        not     null,
    sgsnadd      char(15)        not     null,
    partialtype  char(1)         not     null,
    calltype     char(4)         not     null,
    rattype      char(1)         not     null,
    msisdn       char(13)        not     null,
    vestss       char(3)         not     null,
    bossid       char(10)        not     null,
    permark      char(2)         not     null,
    subpp        char(2)         not     null,
    imsi         char(15)        not     null,
    imei         char(16)        not     null,
    ggsnadd      char(15)        not     null,
    startdate    date            not     null,
    starttime    char(6)         not     null,
    datavolout   bigint          not     null,
    datavolin    bigint          not     null,
    apn          char(20)        not     null,
    sgsnchange   char(20)        not     null,
    chargingid   integer         not     null,
    termind      integer         not     null,
    datatype     char(16)        not     null,
    filedate     date            not     null,
    filename     char(64)        not     null,
    PRIMARY KEY (recid,startdate),
    index ggsncdr_idx1(startdate,subpp,vestss,msisdn,timestamp),
    index ggsncdr_idx2(filedate,filename)
)ENGINE=InnoDB DEFAULT CHARSET=latin1
PARTITION BY RANGE COLUMNS(startdate)
(
  PARTITION p201412 VALUES LESS THAN (('2014-12-01')) ENGINE = InnoDB,
  PARTITION p201501 VALUES LESS THAN (('2015-01-01')) ENGINE = InnoDB,
  PARTITION p201502 VALUES LESS THAN (('2015-02-01')) ENGINE = InnoDB,
  PARTITION p201503 VALUES LESS THAN (('2015-03-01')) ENGINE = InnoDB,
  PARTITION p201504 VALUES LESS THAN (('2015-04-01')) ENGINE = InnoDB,
  PARTITION p201505 VALUES LESS THAN (('2015-05-01')) ENGINE = InnoDB,
  PARTITION p201506 VALUES LESS THAN (('2015-06-01')) ENGINE = InnoDB,
  PARTITION p201507 VALUES LESS THAN (('2015-07-01')) ENGINE = InnoDB,
  PARTITION p201508 VALUES LESS THAN (('2015-08-01')) ENGINE = InnoDB,
  PARTITION p201509 VALUES LESS THAN (('2015-09-01')) ENGINE = InnoDB,
  PARTITION p201510 VALUES LESS THAN (('2015-10-01')) ENGINE = InnoDB,
  PARTITION p201511 VALUES LESS THAN (('2015-11-01')) ENGINE = InnoDB,
  PARTITION p201512 VALUES LESS THAN (('2015-12-01')) ENGINE = InnoDB,
  PARTITION p201601 VALUES LESS THAN (('2016-01-01')) ENGINE = InnoDB,
  PARTITION p201602 VALUES LESS THAN (('2016-02-01')) ENGINE = InnoDB,
  PARTITION p201603 VALUES LESS THAN (('2016-03-01')) ENGINE = InnoDB,
  PARTITION p201604 VALUES LESS THAN (('2016-04-01')) ENGINE = InnoDB,
  PARTITION p201605 VALUES LESS THAN (('2016-05-01')) ENGINE = InnoDB,
  PARTITION p201606 VALUES LESS THAN (('2016-06-01')) ENGINE = InnoDB,
  PARTITION p201607 VALUES LESS THAN (('2016-07-01')) ENGINE = InnoDB,
  PARTITION p201608 VALUES LESS THAN (('2016-08-01')) ENGINE = InnoDB,
  PARTITION p201609 VALUES LESS THAN (('2016-09-01')) ENGINE = InnoDB,
  PARTITION p201610 VALUES LESS THAN (('2016-10-01')) ENGINE = InnoDB,
  PARTITION p201611 VALUES LESS THAN (('2016-11-01')) ENGINE = InnoDB,
  PARTITION p201612 VALUES LESS THAN (('2016-12-01')) ENGINE = InnoDB,
  PARTITION p201701 VALUES LESS THAN (('2017-01-01')) ENGINE = InnoDB,
  PARTITION p201702 VALUES LESS THAN (('2017-02-01')) ENGINE = InnoDB,
  PARTITION p201703 VALUES LESS THAN (('2017-03-01')) ENGINE = InnoDB,
  PARTITION p201704 VALUES LESS THAN (('2017-04-01')) ENGINE = InnoDB,
  PARTITION p201705 VALUES LESS THAN (('2017-05-01')) ENGINE = InnoDB,
  PARTITION p201706 VALUES LESS THAN (('2017-06-01')) ENGINE = InnoDB,
  PARTITION p201707 VALUES LESS THAN (('2017-07-01')) ENGINE = InnoDB,
  PARTITION p201708 VALUES LESS THAN (('2017-08-01')) ENGINE = InnoDB,
  PARTITION p201709 VALUES LESS THAN (('2017-09-01')) ENGINE = InnoDB,
  PARTITION p201710 VALUES LESS THAN (('2017-10-01')) ENGINE = InnoDB,
  PARTITION p201711 VALUES LESS THAN (('2017-11-01')) ENGINE = InnoDB,
  PARTITION p201712 VALUES LESS THAN (('2017-12-01')) ENGINE = InnoDB,
  PARTITION p201801 VALUES LESS THAN (MAXVALUE) ENGINE = InnoDB
);

drop table if exists sgsncdr;
create table sgsncdr (
    recid        integer         not     null auto_increment,
    timestamp    integer         not     null,
    datavol      bigint          not     null,
    duration     integer         not     null,
    pdpadd       char(15)        not     null,
    sgsnadd      char(15)        not     null,
    partialtype  char(1)         not     null,
    calltype     char(4)         not     null,
    rattype      char(1)         not     null,
    msisdn       char(13)        not     null,
    vestss       char(3)         not     null,
    bossid       char(10)        not     null,
    permark      char(2)         not     null,
    subpp        char(2)         not     null,
    imsi         char(15)        not     null,
    imei         char(16)        not     null,
    ggsnadd      char(15)        not     null,
    startdate    date            not     null,
    starttime    char(6)         not     null,
    datavolout   bigint          not     null,
    datavolin    bigint          not     null,
    apn          char(20)        not     null,
    sgsnchange   char(20)        not     null,
    chargingid   integer         not     null,
    termind      integer         not     null,
    datatype     char(16)        not     null,
    filedate     date            not     null,
    filename     char(64)        not     null,
    PRIMARY KEY (recid,startdate),
    index sgsncdr_idx1(startdate,subpp,vestss,msisdn,timestamp),
    index sgsncdr_idx2(filedate,filename)
)ENGINE=InnoDB DEFAULT CHARSET=latin1
PARTITION BY RANGE COLUMNS(startdate)
(
  PARTITION p201412 VALUES LESS THAN (('2014-12-01')) ENGINE = InnoDB,
  PARTITION p201501 VALUES LESS THAN (('2015-01-01')) ENGINE = InnoDB,
  PARTITION p201502 VALUES LESS THAN (('2015-02-01')) ENGINE = InnoDB,
  PARTITION p201503 VALUES LESS THAN (('2015-03-01')) ENGINE = InnoDB,
  PARTITION p201504 VALUES LESS THAN (('2015-04-01')) ENGINE = InnoDB,
  PARTITION p201505 VALUES LESS THAN (('2015-05-01')) ENGINE = InnoDB,
  PARTITION p201506 VALUES LESS THAN (('2015-06-01')) ENGINE = InnoDB,
  PARTITION p201507 VALUES LESS THAN (('2015-07-01')) ENGINE = InnoDB,
  PARTITION p201508 VALUES LESS THAN (('2015-08-01')) ENGINE = InnoDB,
  PARTITION p201509 VALUES LESS THAN (('2015-09-01')) ENGINE = InnoDB,
  PARTITION p201510 VALUES LESS THAN (('2015-10-01')) ENGINE = InnoDB,
  PARTITION p201511 VALUES LESS THAN (('2015-11-01')) ENGINE = InnoDB,
  PARTITION p201512 VALUES LESS THAN (('2015-12-01')) ENGINE = InnoDB,
  PARTITION p201601 VALUES LESS THAN (('2016-01-01')) ENGINE = InnoDB,
  PARTITION p201602 VALUES LESS THAN (('2016-02-01')) ENGINE = InnoDB,
  PARTITION p201603 VALUES LESS THAN (('2016-03-01')) ENGINE = InnoDB,
  PARTITION p201604 VALUES LESS THAN (('2016-04-01')) ENGINE = InnoDB,
  PARTITION p201605 VALUES LESS THAN (('2016-05-01')) ENGINE = InnoDB,
  PARTITION p201606 VALUES LESS THAN (('2016-06-01')) ENGINE = InnoDB,
  PARTITION p201607 VALUES LESS THAN (('2016-07-01')) ENGINE = InnoDB,
  PARTITION p201608 VALUES LESS THAN (('2016-08-01')) ENGINE = InnoDB,
  PARTITION p201609 VALUES LESS THAN (('2016-09-01')) ENGINE = InnoDB,
  PARTITION p201610 VALUES LESS THAN (('2016-10-01')) ENGINE = InnoDB,
  PARTITION p201611 VALUES LESS THAN (('2016-11-01')) ENGINE = InnoDB,
  PARTITION p201612 VALUES LESS THAN (('2016-12-01')) ENGINE = InnoDB,
  PARTITION p201701 VALUES LESS THAN (('2017-01-01')) ENGINE = InnoDB,
  PARTITION p201702 VALUES LESS THAN (('2017-02-01')) ENGINE = InnoDB,
  PARTITION p201703 VALUES LESS THAN (('2017-03-01')) ENGINE = InnoDB,
  PARTITION p201704 VALUES LESS THAN (('2017-04-01')) ENGINE = InnoDB,
  PARTITION p201705 VALUES LESS THAN (('2017-05-01')) ENGINE = InnoDB,
  PARTITION p201706 VALUES LESS THAN (('2017-06-01')) ENGINE = InnoDB,
  PARTITION p201707 VALUES LESS THAN (('2017-07-01')) ENGINE = InnoDB,
  PARTITION p201708 VALUES LESS THAN (('2017-08-01')) ENGINE = InnoDB,
  PARTITION p201709 VALUES LESS THAN (('2017-09-01')) ENGINE = InnoDB,
  PARTITION p201710 VALUES LESS THAN (('2017-10-01')) ENGINE = InnoDB,
  PARTITION p201711 VALUES LESS THAN (('2017-11-01')) ENGINE = InnoDB,
  PARTITION p201712 VALUES LESS THAN (('2017-12-01')) ENGINE = InnoDB,
  PARTITION p201801 VALUES LESS THAN (MAXVALUE) ENGINE = InnoDB
);

create table subpp (
    bossid   char(20) not null,
    permark  char(4)  not null,
    primary key(bossid)
);

create table euserst   (
    uid      integer  not null auto_increment,
    callno   char(12) not null,
    cacode   char(10) not null,
    bossid   char(20) not null,
    callkind char(1)  not null,
    permark  char(4)  not null,
    vestss   char(4)  not null,
    vestarea char(4)  not null,
    imsi     char(18) not null,
    opendate char(10) not null,
    primary key(uid),
    index euserst_idx1(callno,opendate)
);

CREATE FUNCTION `func_get_split_string`(
  f_string varchar(65535),
  f_delimiter varchar(5),
  f_order int
) 
RETURNS varchar(255) CHARSET utf8
BEGIN
  declare result varchar(255) default '';
  set result = reverse(substring_index(reverse(substring_index(f_string,f_delimiter,f_order)),f_delimiter,1));
  return result;
END

CREATE FUNCTION `func_get_split_string_total`(
  f_string varchar(65535),
  f_delimiter varchar(5)
) 
RETURNS int(11)
BEGIN
  return 1+(length(f_string) - length(replace(f_string,f_delimiter,'')));
END

CREATE PROCEDURE `callno_gets`(
  IN calllsit_string varchar(65535),
  IN open_date char(8)
)
BEGIN
  declare cnt int default 0;
  declare i int default 0;
  declare v_uid int default 0;
  declare v_callno char(11) default "";
  set cnt = func_get_split_string_total(calllsit_string,',');
  drop table if exists tmp_callno_list;
  create temporary table tmp_callno_list(uid int not null,callno char(11) not null);
  while i < cnt
  do
    set i = i + 1;
    set v_callno = func_get_split_string(calllsit_string,',',i);
    select max(uid) into v_uid from euserst where callno = v_callno and opendate<=open_date;
    insert into tmp_callno_list(uid,callno) values (v_uid,v_callno);
  end while;
  create unique index callnoidx on tmp_callno_list(uid);
  select a.callno,b.bossid,b.vestss,b.permark,
         (select permark from subpp where bossid = b.bossid) as subpp
    from tmp_callno_list a,euserst b
   where a.uid = b.uid;
END
