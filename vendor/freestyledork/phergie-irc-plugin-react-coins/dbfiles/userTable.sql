CREATE TABLE `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Auto increment of user_id',
  `account` varchar(20) DEFAULT NULL COMMENT 'If registered with freenode',
  `nick` varchar(20) DEFAULT NULL COMMENT 'first Nick used to start playing coins.',
  `worth` bigint(20) DEFAULT '0' COMMENT 'Users current worth',
  `creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'User Creation Time',
  `host` varchar(255) DEFAULT NULL COMMENT 'last seen address should have own table.',
  `type` varchar(10) NOT NULL DEFAULT 'BASIC' COMMENT 'User type: BASIC or ADMIN',
  `status` int(11) NOT NULL DEFAULT '1' COMMENT 'User Status, \n0 : Disabled\n1 : Enabled',
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `id_UNIQUE` (`user_id`),
  UNIQUE KEY `nick_UNIQUE` (`nick`),
  UNIQUE KEY `account_UNIQUE` (`account`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;
