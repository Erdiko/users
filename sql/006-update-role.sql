# update the role name from anonymous to user

UPDATE `roles` SET name = 'user' WHERE name = 'anonymous';
