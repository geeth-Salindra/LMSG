CREATE TABLE IF NOT EXISTS roles (
    id   INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE
);

INSERT IGNORE INTO roles (id, name) VALUES
    (1, 'learner'),
    (2, 'super_admin');

ALTER TABLE learners
    ADD COLUMN IF NOT EXISTS role_id INT NULL;

UPDATE learners
SET role_id = 1
WHERE role_id IS NULL;

ALTER TABLE learners
    MODIFY role_id INT NOT NULL DEFAULT 1;

SET @add_learners_role_fk = IF(
    EXISTS (
        SELECT 1
        FROM information_schema.KEY_COLUMN_USAGE
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'learners'
          AND COLUMN_NAME = 'role_id'
          AND REFERENCED_TABLE_NAME = 'roles'
          AND REFERENCED_COLUMN_NAME = 'id'
    ),
    'SELECT 1',
    'ALTER TABLE learners ADD CONSTRAINT fk_learners_role FOREIGN KEY (role_id) REFERENCES roles(id)'
);
PREPARE add_learners_role_fk FROM @add_learners_role_fk;
EXECUTE add_learners_role_fk;
DEALLOCATE PREPARE add_learners_role_fk;
