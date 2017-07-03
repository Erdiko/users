# update the role name from general to user

UPDATE `roles` SET name = 'user' WHERE name = 'general';
