ALTER TABLE `roles` 
	CHANGE COLUMN `active` `active` tinyint DEFAULT NULL;

ALTER TABLE `user_event_log` 
	CHANGE COLUMN `created_at` `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE `roles` CHANGE COLUMN `active` `active` tinyint DEFAULT NULL, 
	CHANGE COLUMN `created` `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP, 
	CHANGE COLUMN `updated` `updated` timestamp NULL ON UPDATE CURRENT_TIMESTAMP DEFAULT NULL;

ALTER TABLE `users` 
	CHANGE COLUMN `updated_at` `updated_at` timestamp NULL ON UPDATE CURRENT_TIMESTAMP DEFAULT NULL;
