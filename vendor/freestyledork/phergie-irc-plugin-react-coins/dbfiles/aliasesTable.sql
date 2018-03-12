CREATE TABLE `aliases` (
  `alias_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Unique alais id',
  `user_id` int(11) NOT NULL COMMENT 'user_id from users table that the alias is linked to',
  `alias` varchar(20) NOT NULL COMMENT 'alias or irc nick that belongs to an account',
  PRIMARY KEY (`alias_id`),
  UNIQUE KEY `alias_UNIQUE` (`alias`),
  UNIQUE KEY `alias_id_UNIQUE` (`alias_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=latin1;
