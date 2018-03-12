CREATE TABLE `collections` (
  `collection_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Unique collection id',
  `user_id` int(11) DEFAULT NULL COMMENT 'user_id from users table that the collection is linked to',
  `time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT ' auto filled last collection time',
  `amount` int(11) NOT NULL COMMENT 'how much that was gained in this collection',
  PRIMARY KEY (`collection_id`),
  UNIQUE KEY `collection_id_UNIQUE` (`collection_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=latin1;
