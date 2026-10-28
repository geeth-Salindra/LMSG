CREATE TABLE roles (
    id   INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE
);

INSERT INTO roles (id, name) VALUES
    (1, 'learner'),
    (2, 'super_admin');

ALTER TABLE learners
    ADD COLUMN role_id INT NULL;

UPDATE learners
SET role_id = 1
WHERE role_id IS NULL;

ALTER TABLE learners
    MODIFY role_id INT NOT NULL DEFAULT 1,
    ADD CONSTRAINT fk_learners_role
        FOREIGN KEY (role_id) REFERENCES roles(id);
