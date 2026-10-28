UPDATE learners
SET full_name = 'Super Admin',
    password = '$2y$10$JIRVR20i9gZVLAJVOeZCTutSuS.p7pqjPt9uO35WMfaRluT2Ichhy',
    role_id = (SELECT id FROM roles WHERE name = 'super_admin')
WHERE email = 'sadmin@email.com';

INSERT INTO learners (full_name, email, password, role_id)
SELECT
    'Super Admin',
    'sadmin@email.com',
    '$2y$10$JIRVR20i9gZVLAJVOeZCTutSuS.p7pqjPt9uO35WMfaRluT2Ichhy',
    id
FROM roles
WHERE name = 'super_admin'
  AND NOT EXISTS (
      SELECT 1 FROM learners WHERE email = 'sadmin@email.com'
  );
