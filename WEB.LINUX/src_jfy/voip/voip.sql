CREATE DATABASE `voip` DEFAULT CHARACTER SET latin1 default COLLATE  latin1_bin;
use voip;

-- 用户表
drop table IF EXISTS user;
create table user (
    userid        char(8)     binary PRIMARY key,
    username      varchar(32) comment '用户昵称',
    feature       char(16)    comment '功能',
    secure        TINYINT(1)  default 0 comment '加密标志',
    siminfo       char(15)    comment 'SIM信息IMSI',
    msisdn        char(13)    comment '用户手机号码',
    countyrcode   char(3)     comment '国家码',
    zgtflag       char(1)     comment '中港通标志YN',
    usertype      char(16)    comment '用户类型',
    operatorid    char(32)    comment '操作工号',
    createtime    TIMESTAMP   comment '创建时间',
    PRIMARY KEY (msisdn),
    unique index useridx(userid)
) comment '用户资料表';

-- 用户帐户表
drop table IF EXISTS useracnt;
create table useracnt (
    userid        char(8)       binary PRIMARY key ,
    activetime    TIMESTAMP     comment '激活时间',
    amount        INT UNSIGNED  comment '帐户金额(分)',
    validdate     date          comment '有效期',
    status        char(1)       comment '帐户状态',
    nextkfdate    date          comment '下次扣费时间'
) comment '非中港通用户帐户表';

-- 充值记录表
drop table IF EXISTS rechargelog;
create table rechargelog (
    vid        int           auto_increment PRIMARY key,
    userid     char(8)       binary,
    chargetype char(10)      comment '充值类型',
    cardno     char(32)      comment '充值卡号',
    charge     decimal(10,2) comment '充值金额',
    chargetime TIMESTAMP     comment '充值时间',
    validdays  integer       comment '充值有效期天数',
    status     char(1)       comment '充值状态',
    memo       varchar(100)  comment '备注',
    KEY `logi1` (userid)
) comment '充值记录表';

-- 扣费日志表
drop table IF EXISTS charginglog;
create table charginglog (
    vid        integer       auto_increment PRIMARY key,
    userid     char(8)       binary,
    msisdn     char(13)      comment 'MSISDN',
    callnoa    char(64)      comment 'MOC',
    callnob    char(64)      comment 'MTC',
    calltype   char(10)      comment '通话类型',
    amount     decimal(10,2) comment '扣费金额',
    duration   integer       comment '时长',
    callid     varchar(64)   comment '呼叫标识',
    chargetime TIMESTAMP     comment '扣费时间',
    status     char(3)       comment '扣费状态',
    memo       varchar(100)  comment '备注',
    KEY `logi1` (userid,msisdn)
) comment '扣费日志表';

-- oplog
drop table if exists oplog;
CREATE TABLE `oplog` (
  `recid`    int(11)   NOT NULL AUTO_INCREMENT,
  `optime`   TIMESTAMP COLLATE latin1_bin NOT NULL,
  `funcname` char(64)  COLLATE latin1_bin NOT NULL,
  `keykey`   char(64)  COLLATE latin1_bin NOT NULL,
  `jsonreq`  varchar(1024) COLLATE latin1_bin NOT NULL,
  `jsonrep`  varchar(1024) COLLATE latin1_bin NOT NULL,
  PRIMARY KEY (`recid`),
  KEY `oplogi1` (`funcname`,`keykey`,`optime`)
) comment '操作日志表';

