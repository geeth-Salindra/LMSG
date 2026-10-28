CREATE TABLE IF NOT EXISTS institutes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL UNIQUE,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

INSERT IGNORE INTO institutes (name) VALUES ('Institute 1');
INSERT IGNORE INTO roles (name) VALUES ('institute_admin');

ALTER TABLE learners
    ADD COLUMN IF NOT EXISTS institute_id INT NULL;

UPDATE learners
SET institute_id = (SELECT id FROM institutes WHERE name = 'Institute 1')
WHERE institute_id IS NULL
  AND role_id <> (SELECT id FROM roles WHERE name = 'super_admin');

SET @add_learners_institute_fk = IF(
    EXISTS (
        SELECT 1
        FROM information_schema.KEY_COLUMN_USAGE
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'learners'
          AND COLUMN_NAME = 'institute_id'
          AND REFERENCED_TABLE_NAME = 'institutes'
          AND REFERENCED_COLUMN_NAME = 'id'
    ),
    'SELECT 1',
    'ALTER TABLE learners ADD CONSTRAINT fk_learners_institute FOREIGN KEY (institute_id) REFERENCES institutes(id)'
);
PREPARE add_learners_institute_fk FROM @add_learners_institute_fk;
EXECUTE add_learners_institute_fk;
DEALLOCATE PREPARE add_learners_institute_fk;

UPDATE learners
INNER JOIN roles ON roles.id = learners.role_id
INNER JOIN institutes ON institutes.name = 'Institute 1'
SET learners.full_name = CASE
        WHEN learners.email = 'institute1@gmail.com' THEN 'Institute 1 Admin'
        WHEN learners.email = 'institute_admin_1@gmail.com' THEN 'Institute Admin 1'
        ELSE learners.full_name
    END,
    learners.password = '$2y$10$BD0JR0IfgZ6PwgM7HLgijuKkENg/wB9tdD/xb.Og8DPVVX9madg1O',
    learners.role_id = (SELECT id FROM roles WHERE name = 'institute_admin'),
    learners.institute_id = institutes.id
WHERE learners.email IN ('institute1@gmail.com', 'institute_admin_1@gmail.com');

INSERT INTO learners (full_name, email, password, role_id, institute_id)
SELECT seed.full_name, seed.email, seed.password, roles.id, institutes.id
FROM (
    SELECT 'Institute 1 Admin' AS full_name, 'institute1@gmail.com' AS email,
           '$2y$10$BD0JR0IfgZ6PwgM7HLgijuKkENg/wB9tdD/xb.Og8DPVVX9madg1O' AS password
    UNION ALL
    SELECT 'Institute Admin 1', 'institute_admin_1@gmail.com',
           '$2y$10$BD0JR0IfgZ6PwgM7HLgijuKkENg/wB9tdD/xb.Og8DPVVX9madg1O'
) AS seed
INNER JOIN roles ON roles.name = 'institute_admin'
INNER JOIN institutes ON institutes.name = 'Institute 1'
WHERE NOT EXISTS (
    SELECT 1 FROM learners WHERE learners.email = seed.email
);
