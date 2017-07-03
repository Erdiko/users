# Add unique constraint

ALTER TABLE `users` DROP INDEX `idx_email`, ADD UNIQUE `idx_email` USING BTREE (`email`) comment 'Unique email constraint';
