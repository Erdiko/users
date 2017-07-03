## insert each of basic roles if not exists in roles table.

INSERT INTO roles (name, active, created)
  SELECT 'general','1',NOW() FROM DUAL
  WHERE NOT EXISTS
  (SELECT name FROM roles WHERE name='general');

INSERT INTO roles (name, active, created)
  SELECT 'super','1',NOW() FROM DUAL
  WHERE NOT EXISTS
  (SELECT name FROM roles WHERE name='super');

INSERT INTO roles (name, active, created)
  SELECT 'client','1',NOW() FROM DUAL
  WHERE NOT EXISTS
  (SELECT name FROM roles WHERE name='client');

INSERT INTO roles (name, active, created)
  SELECT 'super_admin','1',NOW() FROM DUAL
  WHERE NOT EXISTS
  (SELECT name FROM roles WHERE name='super_admin');