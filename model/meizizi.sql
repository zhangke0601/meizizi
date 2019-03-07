
drop database if exists meizizi;

create database meizizi character set utf8;

use meizizi;
/* 用户信息表 */
create table userinfos(
  /*{{{*/
  uid int(32) auto_increment primary key,
  utype int(32) default 0, /*0管理员 5漫画师 10工作室 15工作室管理员*/
  uname varchar(100) not null,
  urealname varchar(100) default '', /* 真实姓名 */
  umobile varchar(15) unique,
  uemail varchar(100) unique,
  upasswd varchar(50) not null,
  usex tinyint(4) default 0,
  ugroupid int(32) default 0,   /* 组id */
  sugid int(32) default 0,  /* 工作室组id */
  udesc varchar(1000) default '',
  unotice varchar(1000) default '',
  upuid int(32) default 0,
  udataauth int(32) default 0,/*0未授权 5 已授权*/
  uqudaodesc int(32) default 0, /*0未查看 5已查看*/
  ulastlogintime varchar(20) default '',  /* 最后登录时间 */
  ulastloginip varchar(20) default '',
  ulogintimes int(32) default 0,
  usort int(32) default 0,
  ustate int(32) default 0,
  ucreatetime timestamp default current_timestamp,
  uupdatetime timestamp
);/*}}}*/

insert into userinfos(umobile,upasswd) values('1','1');
insert into userinfos(umobile,upasswd,urealname,utype) values('15910350235','111111','冯二波',5);
/* 用户组信息 */
create table usergroupinfos(
  /*{{{*/
  ugid int(32) auto_increment primary key,
  ugname varchar(50) not null,
  ugfuncinfos varchar(500) default '',
  ugdesc varchar(500) default '',
  ugstate int(32) default 0,
  ugcreatetime timestamp default current_timestamp,
  ugupdatetime timestamp
);/*}}}*/
/* 作品信息 */
create table userlogininfos(
  /*{{{*/
  ulid int(32) auto_increment primary key,
  utype int(32) not null,
  uid int(32) not null,
  ullogintime varchar(20) default '',
  ulloginip varchar(20) default '',
  ulstate int(32) default 0,
  ulcreatetime timestamp default current_timestamp,
  ulupdatetime timestamp
);/*}}}*/

create table cartooninfos(
  /*{{{*/
  ctid int(32) auto_increment primary key,
  uid int(32) not null,  /* 用户id */
  ctname varchar(100) not null,
  ctlatestname varchar(100) default '',
  cttid1 int(32) default 0,
  cttid2 int(32) default 0,
  ctauthorname varchar(100) not null,
  ctimageauthor varchar(100) not null,
  cttextauthor varchar(100) not null,
  cttpid int(32) default 0,
  ctsuid int(32) default 0,
  ctprogress int(32) default 0,
  ctfirstrelease int(32) default 0,/*首发状态*/
  ctauthstate int(32) default 0,/*授权状态*/
  ctvector int(32) default 0,/*漫画载体*/
  ctusergroup int(32) default 0,/*用户群 少年少女*/
  ctverticalimage varchar(255) default '',/*竖封面*/
  cthorizontalimage varchar(255) default '',/*横封面*/
  ctdesc varchar(500) default '',
  ctnotice varchar(500) default '',
  ctrealname varchar(50) default '',
  ctidcardnum varchar(25) default '',
  ctcontact varchar(20) default '',
  ctbrowsercount int(32) default 0,
  ctcollectcount int(32) default 0,
  ctsectioncount int(32) default 0,
  ctsource int(32) default 0,
  ctsourceid varchar(50) default '',/*来源网站上的ID，比如腾讯网站的ID*/
  cttype int(32) default 1,/*1表示用户编辑，5第三方网站抓取，10管理员添加*/
  ctparentid int(32) default 0, /*父ID，用于用户提交的漫画信息，不为0时，cssource不为0*/
  ctprices varchar(500) default '',/*各个平台VIP价格*/
  ctstate int(32) default 0,
  ctcreatetime timestamp default current_timestamp,
  ctupdatetime timestamp
);/*}}}*/

/* 作品封面和平台关联表 */
create table cartooncoverandplatforminfos(
  ccpid int(32) auto_increment primary key,
  ctid int(32) default 0,
  pfid int(32) default 0,
  ccpverticalimg varchar(255),
  ccphorizontalimg varchar(255),
  ccpstate int(11) default 0,
  ccpcreatetime timestamp default current_timestamp,
  ccpupdatetime timestamp default '0000-00-00 00:00:00'
);

/* 作品来源信息 */
create table cartoonsourceinfos(
  /*{{{*/
  csid int(32) auto_increment primary key,
  ctid int(32) not null,        /* 作品id */
  cssource int(32) default 0,/*来源 腾讯、快看、漫漫等*/
  cssourceid varchar(50),
  cssourcebakid varchar(50),
  cssourceurl varchar(255) default '',
  csreleasetime varchar(20) default '',
  csreleasetype int(32) default 0, /*发布类型0没有显示，5独家*/
  csfirstrelease int(32) default 0,/*首发状态0没有显示，5首发*/
  csdividexishu varchar(10) default '',
  csprevtype int(6) default 0,
  csprevvalue varchar(20) default '',
  csextra text,
  csreason varchar(500) default '',
  csstate int(32) default 0,
  cscreatetime timestamp default current_timestamp,
  csupdatetime timestamp,
  unique (ctid,cssource,cssourceid)
);/*}}}*/
/* 卡通 标签关联表*/
create table cartoonandtaginfos(
  /*{{{*/
  catid int(32) auto_increment primary key,
  ctid int(32) not null,            /* 作品id */
  cttid int(32) not null,
  catstate int(32) default 0,
  catcreatetime timestamp default current_timestamp,
  catupdatetime timestamp
);/*}}}*/
/* 作品和类型关联表*/
create table cartoonandtypeinfos(
  /*{{{*/
  catpid int(32) auto_increment primary key,
  ctid int(32) not null,                    /* 作品id */
  cttpid int(32) not null,
  catpstate int(32) default 0,
  catpcreatetime timestamp default current_timestamp,
  catpupdatetime timestamp
);/*}}}*/
/* 作品章节信息 */
create table cartoonsectioninfos(
  /*{{{*/
  ctsid int(32) auto_increment primary key,
  ctid int(32) not null,            /* 作品id */
  ctsname varchar(100) not null,
  ctsvip int(32) default 0, /*0非VIP 5VIP*/
  ctssort int(32) default 0,
  ctscover varchar(255) default '',
  ctscontent text,
  ctssource int(32) default 0,
  ctssourceid varchar(50) default '',
  ctstotal int(32) default 0,
  ctsplatformcoverinfos text,
  ctstailframeinfos text,
  ctsparentid int(32) default 0,
  ctsstate int(32) default 0,
  ctscreatetime timestamp default current_timestamp,
  ctsupdatetime timestamp
);/*}}}*/
/* 作品数据信息 */
create table cartoondatainfos(
  /*{{{*/
  ctdid int(32) auto_increment primary key,
  ctid int(32) default 0,                     /* 作品id */
  ctdsource int(32) default 0,
  ctdexclusive int(32) default 0,
  ctdbrowsercount bigint(64) default 0, /*浏览点击*/
  ctdcollectcount int(32) default 0,
  ctdzancount int(32) default 0,
  ctdredu bigint(64) default 0,
  ctdfee int(32) default 0,/*0表示不收费，1表示收费*/
  ctddatetime varchar(25) default '',
  ctdtimestamp varchar(25) default '',
  ctdtotalticket int(32) default 0,
  ctdmonthticket int(32) default 0,
  ctdmonthticketrank int(32) default 0,
  ctdgrade float default 0,
  ctdgradecount int(32) default 0,
  ctdprice varchar(100) default '',
  ctdpriceval varchar(100) default '', /*原始信息*/
  ctdcommentcount int(32) default 0,
  ctdtuijiancount int(32) default 0,
  ctdrank int(32) default 0,
  ctdkeyname varchar(200) default '',
  ctdcreateat varchar(20) default '',
  ctdupdateat varchar(20) default '',
  ctdupdateatval varchar(20) default '',
  ctdname varchar(100) default '',
  ctdauthorname varchar(100) default '',
  ctdprogress int(32) default 0,
  ctdsectioncount int(32) default 0,
  ctdsourceid varchar(50) default '',
  ctdstate int(32) default 0,
  ctdcreatetime timestamp default current_timestamp,
  ctdupdatetime timestamp,
  unique (ctid,ctdsource,ctddatetime)
);/*}}}*/

/*存放用户上传和同步的漫画抓取信息*/
create table cartoonselfdatainfos(
  /*{{{*/
  ctsdid int(32) auto_increment primary key,
  ctid int(32) not null,
  pfid int(32) not null,
  ctsdbrowsercount bigint(64) default 0,
  ctsdcollectcount int(32) default 0,
  ctsdtucaocount int(32) default 0,
  ctsdtotalincome varchar(30) default '',
  ctsdsaletotalincome varchar(30) default '',
  ctsdshangtotalincome varchar(30) default '',
  ctsdupdateat varchar(25) default '',
  ctsddaytucaocount int(32) default 0,
  ctsddaybrowsercount int(32) default 0,
  ctsddaycollectcount int(32) default 0,
  ctsddayincome varchar(20) default '',
  ctsddaysaleincome varchar(20) default '',
  ctsddayshangincome varchar(20) default '',
  ctsdday varchar(20) not null,
  ctsdextra text,
  ctsdcreatetime timestamp default current_timestamp,
  ctsdupdatetime timestamp,
  unique (ctid,pfid,ctsdday)
);/*}}}*/

create table cartoonrankinfos(
  /*{{{*/
  crid int(32) auto_increment primary key,
  ctid int(32) default 0,
  crsource int(32) default 0,
  crsourceid varchar(50) default '',
  crname varchar(200) default '',
  crauthorname varchar(200) default '',
  crtype varchar(10) default '',
  crrank int(32) default 0,
  crdatetime varchar(20) default '',
  crdatetimeval varchar(20) default '',
  crspecialinfo varchar(1000) default '',
  crcreatetime timestamp default current_timestamp,
  crupdatetime timestamp,
  unique (crsource,crsourceid,crrank,crdatetime)
);/*}}}*/

/* 作品章节内容信息 */
create table cartoonsectiondatainfos(
  /*{{{*/
  ctsdid int(32) auto_increment primary key,
  ctsid int(32) default 0,        /* 章节id */
  pfid int(32) default 0, /*TODO*/
  ctsdsource int(32) default 0,
  ctsdprice int(32) default 0, /*是否收费*/
  ctsdpriceval varchar(10) default '', /*具体费用*/
  ctsdbrowsercount bigint(64) default 0,
  ctsdcollectcount int(32) default 0,
  ctsdgoodcount int(32) default 0,
  ctsdcommentcount int(32) default 0,
  ctsddatetime varchar(25) default '',
  ctsdtimestamp varchar(25) default '',
  ctsdstate int(32) default 0,
  ctsdcreatetime timestamp default current_timestamp,
  ctsdupdatetime timestamp,
  unique (ctsid,ctsdsource,ctsddatetime)
);/*}}}*/
/* 平台 信息  */
create table platforminfos(
  /*{{{*/
  pfid int(32) auto_increment primary key,
  pfname varchar(50) not null,
  pfuploadurl varchar(255) default '', /* 上传地址 */
  pfusername varchar(50) default '',   /* 用户名称  */
  pfpassword varchar(50) default '',   /*  */
  pfdesc varchar(500) default '',       /* 描述 */
  pfsort int(32) default 0,             /* 种类 分类 */
  pfstate int(32) default 0,
  pfcreatetime timestamp default current_timestamp,
  pfupdatetime timestamp
);/*}}}*/
/* 用户和平台关联表信息 */
create table userandplatforminfos(
  /*{{{*/
  upfid int(32) auto_increment primary key,
  uid int(32) not null, /* 用户id */
  pfid int(32) not null,
  upftype int(32) default 0,/*使用美滋滋账号1，还是使用自有账号2*/
  upfusername varchar(50) default '',
  upfpassword varchar(50) default '',
  upfcookies text,
  upfcookiesstate int(32) default 0, /*0不处理，5失效 10已提交*/
  upfstate int(32) default 0,
  upfcreatetime timestamp default current_timestamp,
  upfupdatetime timestamp
);/*}}}*/

/*漫画同步记录*/
create table platformaccountsyncinfos(
  /*{{{*/
  pasid int(32) auto_increment primary key,
  uid int(32) not null,
  upfid int(32) default 0,
  pfid int(32) default 0,
  upfusername varchar(50) default '',
  upfpassword varchar(50) default '',
  pascookies text default '',
  passtate int(32) default 0,
  pascreatetime timestamp default current_timestamp,
  pasupdatetime timestamp
);/*}}}*/

/* 作品发布信息 */
create table cartoonreleaseinfos(
  /*{{{*/
  ctrid int(32) auto_increment primary key,
  uid int(32) default 0,      /* 用户id */
  ctid int(32) not null,     /* 作品id */
  ctsid int(32) not null,     /* 章节id */
  ctrfixedtime varchar(20) default '',
  ctrstate int(32) default 0,
  ctrcreatetime timestamp default current_timestamp,
  ctrupdatetime timestamp
);/*}}}*/
/* 作品发布记录信息 */
create table cartoonreleaserecordinfos(
  /*{{{*/
  ctrrid int(32) auto_increment primary key,
  ctrid int(32) default 0,     /* 作品发布信息 id  */
  ctid int(32) default 0,      /*漫画ID*/
  ctsid int(32) default 0,     /*章节ID*/
  pfid int(32) default 0,      /* 平台id */
  upfid int(32) default 0,      /* 用户和平台关联表id */
  upftype int(32) default 0,/*使用美滋滋账号1，还是使用自有账号2*/
  ctrrreleasetime varchar(20) default '',
  ctrrpfsectionid varchar(50) default '',
  ctrrpfsectionbakid varchar(50) default '',
  ctrrcookies text,
  ctrrreason text,
  ctrrtype int(32) default 0, /*0表示用户发布 5表示已经发布过*/
  ctrrstate int(32) default 0,
  ctrrcreatetime timestamp default current_timestamp,
  ctrrupdatetime timestamp
);/*}}}*/
/* 作品 标签信息 */
create table cartoontaginfos(
  /*{{{*/
  cttid int(32) auto_increment primary key,
  cttname varchar(50) not null,
  cttsort int(32) default 0,
  cttdesc varchar(500) default '',
  cttstate int(32) default 0,
  cttcreatetime timestamp default current_timestamp,
  cttupdatetime timestamp
);/*}}}*/
/*insert into cartoontaginfos(cttname) values('校园');
insert into cartoontaginfos(cttname) values('恐怖');*/
/* 作品 类型 信息 */
create table cartoontypeinfos(
  /*{{{*/
  cttpid int(32) auto_increment primary key,
  cttpname varchar(50) not null,
  cttpsort int(32) default 0,
  cttpdesc varchar(500) default '',
  cttpstate int(32) default 0,
  cttpcreatetime timestamp default current_timestamp,
  cttpupdatetime timestamp
);/*}}}*/
/*insert into cartoontypeinfos(cttpname) values('同人');
insert into cartoontypeinfos(cttpname) values('连环画');*/

/* 作品题材 */
create table cartoonsubjectinfos(
  /*{{{*/
  ctsuid int(32) auto_increment primary key,
  ctsuname varchar(50) not null,
  ctsusort int(32) default 0,
  ctsudesc varchar(500) default '',
  ctsustate int(32) default 0,
  ctsucreatetime timestamp default current_timestamp,
  ctsuupdatetime timestamp
);/*}}}*/

create table cartoonandsubjectinfos(
  /*{{{*/
  casid int(32) auto_increment primary key,
  ctid int(32) not null,                    /* 作品id */
  ctsuid int(32) not null,
  casstate int(32) default 0,
  cascreatetime timestamp default current_timestamp,
  casupdatetime timestamp
);/*}}}*/
/*insert into cartoonsubjectinfos(ctsuname) values('感情类');
insert into cartoonsubjectinfos(ctsuname) values('动作类');*/
/* 功能信息 */
create table functioninfos(
  /*{{{*/
  fid int(32) auto_increment primary key,
  ftype int(32) default 0,                    /*0表示链接（URL）权限；5表示仅有权限，没有URL*/
  fname varchar(40) not null,
  furl varchar(200),
  fsort int(32) default 0,
  flevel int(32) default 0, /*1表示一级，2表示二级，只有两级；功能性权限没有级别*/
  fupid int(32) default 0,
  fstate int(32) default 0,
  fcreatetime timestamp default current_timestamp,
  fupdatetime timestamp
);/*}}}*/
/* 组和功能关联表信息 */
create table groupandfuncinfos(
  /*{{{*/
  gfid int(32) auto_increment primary key,
  gid int(32) not null,   /*  组id */
  fid int(32) not null,   /* 功能id */
  gfstate int(32) default 0,
  gfcreatetime timestamp default current_timestamp,
  gfupdatetime timestamp
);/*}}}*/

create table noticeinfos(
  /*{{{*/
  nid int(32) auto_increment primary key,
  ntitle varchar(255) default '',
  ncontent varchar(1000) default '',
  nattachments varchar(1000) default '',
  nposttype int(32) default 0,/**/
  nusertype int(32) default 0,
  nuserlist text,
  nstate int(32) default 0,
  ncreatetime timestamp default current_timestamp,
  nupdatetime timestamp
);/*}}}*/

create table userandnoticeinfos(
  /*{{{*/
  uanid int(32) auto_increment primary key,
  uid int(32) not null,
  nid int(32) not null,
  uanstate int(32) default 0,
  uancreatetime timestamp default current_timestamp,
  uanupdatetime timestamp
);/*}}}*/

create table thirduserinfos(
  /*{{{*/
  tuid int(32) auto_increment primary key,
  tuname varchar(50) not null,
  tuauthenddate varchar(10) default ''.
  tuaccesstoken varchar(50) not null,
  tuauthstate int(32) default 0,
  tudesc text,
  tustate int(32) default 0,
  tucreatetime timestamp default current_timestamp,
  tuupdatetime timestamp
);/*}}}*/

create table thirduseranduserinfos(
  /*{{{*/
  tuuid int(32) auto_increment primary key,
  tuid int(32) not null,
  uid int(32) not null,
  tuucpid varchar(20) default '',
  tuuauthenddate varchar(10) default ''.
  tuustate int(32) default 0,
  tuucreatetime timestamp default current_timestamp,
  tuuypdatetime timestamp
);/*}}}*/

create table thirduserandcartooninfos(
  /*{{{*/
  tucid int(32) auto_increment primary key,
  tuid int(32) not null,
  tuctype int(32) default 0 COMMENT '0表示自动，5表示手工选择章节',
  ctid int(32) not null,
  tucprice varchar(10) default '',
  tucsectionlist text,
  tucstate int(32) default 0,
  tuccreatetime timestamp default current_timestamp,
  tucupdatetime timestamp
);/*}}}*/

/* 用户操作记录表 */
create table useroperaterecordinfos(
  /*{{{*/
  rid int(32) auto_increment primary key,
  uid int(32) default 0,
  rutype int(32) default 0, /*5表示管理员 10表示工人，15表示商家，20表示天猫，80表示业主，85表示城市合伙人*/
  mobile varchar(20) default '',
  rotype int(33) default 0,
  roid int(32) default 0,   /*对象ID*/
  ractiontype varchar(100) not null,
  rcontent text,
  rrefer varchar(1024) not null,
  rdifftime float default 0,
  rinserttime timestamp default current_timestamp,
  rupdatetime timestamp
);/*}}}*/
/* 反馈信息表 */
create table feedback(
  /*{{{*/
  fbid int(32) auto_increment primary key,
  uid int(32) not null,
  utype int(32) default 0,
  fbcontent varchar(1000) not null,
  fbstate int(6) default 0,
  fbinsertime timestamp default current_timestamp,
  fbupdatetime timestamp
);/*}}}*/
/*  */
create table statfordaily(
  /*{{{*/
  sid int(32) auto_increment primary key,
  `date` varchar(10) unique key,
  totalusercount int(32) not null,
  newusercount int(32) not null,
  subscribecount int(32) not null,
  newsubscribecount int(32) not null,
  activeusercount int(32) not null,
  visitcount int(32) not null,
  avgvisitcount int(32) not null,
  purchasecount int(32) not null,
  collectcount  int(32) not null,
  sharebtncount int(32) not null,
  sharecount    int(32) not null,
  shareclickcount int(32) not null,
  commentcount  int(32) not null,
  top500 MEDIUMTEXT not null,
  inserttime timestamp default current_timestamp
);/*}}}*/

/* 工作室用户组信息 */
create table studiousergroupinfos(
  /*{{{*/
  sugid int(32) auto_increment primary key,
  uid int(32) not null ,  /* 工作室用户id */
  sugname varchar(50) not null,
  sugfuncinfos varchar(500) default '',
  sugdesc varchar(500) default '',
  sugstate int(32) default 0,
  sugcreatetime timestamp default current_timestamp,
  sugupdatetime timestamp
);/*}}}*/

/* 工作室功能信息 */
create table studiofunctioninfos(
  /*{{{*/
  fid int(32) auto_increment primary key,
  ftype int(32) default 0,                    /*0表示链接（URL）权限；5表示仅有权限，没有URL*/
  fname varchar(40) not null,
  furl varchar(200),
  fsort int(32) default 0,
  flevel int(32) default 0, /*1表示一级，2表示二级，只有两级；功能性权限没有级别*/
  fupid int(32) default 0,
  fstate int(32) default 0,
  fcreatetime timestamp default current_timestamp,
  fupdatetime timestamp
);/*}}}*/

create table constiteminfos(
  /*{{{*/
  ciid int(32) auto_increment primary key,
  ciname varchar(100) not null,
  civalue varchar(255) not null,
  cistate int(32) default 0,
  cicreatetime timestamp default current_timestamp,
  ciupdatetime timestamp
);/*}}}*/

/* 添加 字段 */
/*alter table userinfos add sugid int(32) default 0;*/

