# ER-131: expand the field to be larger than 255 chars
ALTER TABLE `user-admin`.`user_event_log` CHANGE COLUMN `event_data` `event_data` text CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL;
