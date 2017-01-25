CREATE TABLE IF NOT EXISTS `user_event_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `event_log` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `event_data` varchar(255) COLLATE utf8_unicode_ci NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1