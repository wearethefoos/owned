CREATE TABLE IF NOT EXISTS `owners` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `model` varchar(64) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `foreign_key` bigint(20) NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `c` int(3) NOT NULL,
  `r` int(3) NOT NULL,
  `u` int(3) NOT NULL,
  `d` int(3) NOT NULL,
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `foreign_key` (`foreign_key`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;