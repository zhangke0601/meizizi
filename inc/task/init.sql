drop table if exists asynctasks;

CREATE TABLE `asynctasks` (
      `id` int(11) unsigned NOT NULL auto_increment,
      `obj` varchar(255) NOT NULL,
      `args` text,
      `createtime` datetime NOT NULL,
      `status` tinyint(1) default NULL,
      `type` tinyint(1) default NULL,
      `retry` tinyint(1) default NULL,
      `nowtry` tinyint(1) default NULL,
      `lastmodifytime` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
      PRIMARY KEY  (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10352 DEFAULT CHARSET=utf8

