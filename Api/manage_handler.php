<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');

function manageJsonReply($status, $success, $message, $extra = [])
{
    http_response_code($status);
    echo json_encode(array_merge(
        ['success' => $success, 'message' => $message],
        $extra
    ));
    exit;
}

$userRole = $_SESSION['user_role'] ?? '';
$isSuperAdmin = $userRole === 'super_admin';
$isInstituteAdmin = $userRole === 'institute_admin';
if (empty($_SESSION['user_id']) || (!$isSuperAdmin && !$isInstituteAdmin)) {
    manageJsonReply(403, false, 'Administrator access is required.');
}

if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'POST'], true)) {
    header('Allow: GET, POST');
    manageJsonReply(405, false, 'Method not allowed.');
}

require_once __DIR__ . '/../includes/database.php';

try {
    $pdo = getConnection();

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        if ($isInstituteAdmin && (int)($_SESSION['institute_id'] ?? 0) < 1) {
            manageJsonReply(403, false, 'Your account is not assigned to an institute.');
        }
        $roleFilter = $isInstituteAdmin
            ? "roles.name IN ('learner', 'institute_admin')"
            : "roles.name = 'learner'";
        $sql = "SELECT learners.id, learners.full_name, learners.email,
                       learners.created_at, learners.is_active,
                       roles.name AS role_name, institutes.name AS institute_name
                FROM learners
                INNER JOIN roles ON roles.id = learners.role_id
                LEFT JOIN institutes ON institutes.id = learners.institute_id
                WHERE $roleFilter";
        $params = [];
        if ($isInstituteAdmin) {
            $sql .= ' AND learners.institute_id = ?';
            $params[] = (int)$_SESSION['institute_id'];
        }
        $sql .= ' ORDER BY learners.full_name, learners.id';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $accounts = $stmt->fetchAll();
        $extra = ['learners' => $accounts];
        if ($isInstituteAdmin) {
            $extra['learners'] = array_values(array_filter(
                $accounts,
                static function ($account) {
                    return $account['role_name'] === 'learner';
                }
            ));
            $extra['institute_admins'] = array_values(array_filter(
                $accounts,
                static function ($account) {
                    return $account['role_name'] === 'institute_admin';
                }
            ));
            $institute = $pdo->prepare(
                'SELECT id, name, created_at FROM institutes WHERE id = ? AND is_active = 1'
            );
            $institute->execute([(int)$_SESSION['institute_id']]);
            $extra['institute'] = $institute->fetch();
        }
        if ($isSuperAdmin) {
            $extra['institutes'] = $pdo->query(
                'SELECT id, name, created_at FROM institutes ORDER BY name, id'
            )->fetchAll();
            $extra['institute_admins'] = $pdo->query(
                "SELECT learners.id, learners.full_name, learners.email,
                        learners.created_at, learners.is_active,
                        institutes.name AS institute_name
                 FROM learners
                 INNER JOIN roles ON roles.id = learners.role_id
                 LEFT JOIN institutes ON institutes.id = learners.institute_id
                 WHERE roles.name = 'institute_admin'
                 ORDER BY institutes.name, learners.full_name, learners.id"
            )->fetchAll();
        }
        manageJsonReply(200, true, 'Learners loaded.', $extra);
    }

    $sentToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    $sessionToken = $_SESSION['csrf_token'] ?? '';
    if ($sessionToken === '' || !hash_equals($sessionToken, $sentToken)) {
        manageJsonReply(403, false, 'Security token expired. Refresh the page and try again.');
    }

    $data = json_decode(file_get_contents('php://input'), true);
    if (is_array($data) && ($data['action'] ?? '') === 'change_role') {
        if (!$isInstituteAdmin) {
            manageJsonReply(403, false, 'Institute Admin access is required to change roles.');
        }

        $learnerId = filter_var($data['learner_id'] ?? null, FILTER_VALIDATE_INT);
        $roleName = $data['role_name'] ?? '';
        $instituteId = (int)($_SESSION['institute_id'] ?? 0);
        if ($learnerId === false || $learnerId === null || $learnerId < 1
            || !in_array($roleName, ['learner', 'institute_admin'], true)
            || $instituteId < 1
        ) {
            manageJsonReply(422, false, 'A valid account and institute role are required.');
        }
        if ($learnerId === (int)($_SESSION['user_id'] ?? 0)) {
            manageJsonReply(422, false, 'You cannot change your own institute role.');
        }

        $pdo->beginTransaction();
        $lockInstitute = $pdo->prepare('SELECT id FROM institutes WHERE id = ? FOR UPDATE');
        $lockInstitute->execute([$instituteId]);
        if (!$lockInstitute->fetch()) {
            $pdo->rollBack();
            manageJsonReply(403, false, 'Your institute is no longer available.');
        }

        $account = $pdo->prepare(
            "SELECT learners.id, learners.institute_id, roles.name AS role_name
             FROM learners
             INNER JOIN roles ON roles.id = learners.role_id
             WHERE learners.id = ?
               AND learners.institute_id = ?
               AND roles.name IN ('learner', 'institute_admin')
             FOR UPDATE"
        );
        $account->execute([$learnerId, $instituteId]);
        $target = $account->fetch();
        if (!$target) {
            $pdo->rollBack();
            manageJsonReply(404, false, 'Account not found in your institute.');
        }
        $instituteId = (int)$target['institute_id'];
        if ($instituteId < 1) {
            $pdo->rollBack();
            manageJsonReply(422, false, 'The account is not assigned to an institute.');
        }
        if ($target['role_name'] === $roleName) {
            $pdo->rollBack();
            manageJsonReply(422, false, 'The account already has that role.');
        }

        if ($roleName === 'learner') {
            $otherAdmins = $pdo->prepare(
                "SELECT COUNT(*)
                 FROM learners
                 INNER JOIN roles ON roles.id = learners.role_id
                 WHERE learners.institute_id = ?
                   AND roles.name = 'institute_admin'
                   AND learners.is_active = 1
                   AND learners.id <> ?"
            );
            $otherAdmins->execute([$instituteId, $learnerId]);
            if ((int)$otherAdmins->fetchColumn() === 0) {
                $pdo->rollBack();
                manageJsonReply(422, false, 'Your institute must have at least one active institute admin.');
            }
        }

        $updateRole = $pdo->prepare(
            'UPDATE learners SET role_id = (SELECT id FROM roles WHERE name = ?) WHERE id = ? AND institute_id = ?'
        );
        $updateRole->execute([$roleName, $learnerId, $instituteId]);
        $pdo->commit();
        manageJsonReply(200, true, $roleName === 'institute_admin'
            ? 'Learner promoted to institute admin.'
            : 'Institute admin demoted to learner.');
    }

    if (is_array($data) && ($data['action'] ?? '') === 'create_institute_admin') {
        if (!$isSuperAdmin) {
            manageJsonReply(403, false, 'Super Admin access is required to create institute admins.');
        }

        $adminName = trim($data['admin_name'] ?? '');
        $adminEmail = trim($data['admin_email'] ?? '');
        $adminPassword = $data['admin_password'] ?? '';
        $instituteId = filter_var($data['institute_id'] ?? null, FILTER_VALIDATE_INT);
        if ($adminName === '' || strlen($adminName) > 100
            || !filter_var($adminEmail, FILTER_VALIDATE_EMAIL) || strlen($adminEmail) > 150
            || strlen($adminPassword) < 6
            || $instituteId === false || $instituteId === null || $instituteId < 1
        ) {
            manageJsonReply(422, false, 'Enter a valid administrator name, email, password, and institute.');
        }

        $pdo->beginTransaction();
        $instituteCheck = $pdo->prepare(
            'SELECT id FROM institutes WHERE id = ? AND is_active = 1 FOR UPDATE'
        );
        $instituteCheck->execute([$instituteId]);
        if (!$instituteCheck->fetch()) {
            $pdo->rollBack();
            manageJsonReply(422, false, 'Select an active institute.');
        }

        $emailCheck = $pdo->prepare('SELECT id FROM learners WHERE email = ?');
        $emailCheck->execute([$adminEmail]);
        if ($emailCheck->fetch()) {
            $pdo->rollBack();
            manageJsonReply(409, false, 'That email address is already registered.');
        }

        $createAdmin = $pdo->prepare(
            "INSERT INTO learners (full_name, email, password, role_id, institute_id)
             SELECT ?, ?, ?, roles.id, ?
             FROM roles
             WHERE roles.name = 'institute_admin'"
        );
        $createAdmin->execute([
            $adminName,
            $adminEmail,
            password_hash($adminPassword, PASSWORD_DEFAULT),
            $instituteId
        ]);
        if ($createAdmin->rowCount() !== 1) {
            $pdo->rollBack();
            manageJsonReply(500, false, 'Institute administrator role is missing. Run the migrations.');
        }
        $pdo->commit();
        manageJsonReply(201, true, 'Institute administrator created.');
    }

    if (is_array($data) && ($data['action'] ?? '') === 'create_institute') {
        if (!$isSuperAdmin) {
            manageJsonReply(403, false, 'Super Admin access is required to create institutes.');
        }
        $name = trim($data['name'] ?? '');
        $adminName = trim($data['admin_name'] ?? '');
        $adminEmail = trim($data['admin_email'] ?? '');
        $adminPassword = $data['admin_password'] ?? '';
        if ($name === '' || strlen($name) > 150 || $adminName === '' || strlen($adminName) > 100
            || !filter_var($adminEmail, FILTER_VALIDATE_EMAIL) || strlen($adminEmail) > 150
            || strlen($adminPassword) < 6
        ) {
            manageJsonReply(422, false, 'Enter a valid institute and institute administrator. Password must be at least 6 characters.');
        }

        $pdo->beginTransaction();
        $emailCheck = $pdo->prepare('SELECT id FROM learners WHERE email = ? FOR UPDATE');
        $emailCheck->execute([$adminEmail]);
        if ($emailCheck->fetch()) {
            $pdo->rollBack();
            manageJsonReply(409, false, 'That administrator email is already registered.');
        }

        $createInstitute = $pdo->prepare('INSERT INTO institutes (name) VALUES (?)');
        $createInstitute->execute([$name]);
        $instituteId = (int)$pdo->lastInsertId();
        $createAdmin = $pdo->prepare(
            "INSERT INTO learners (full_name, email, password, role_id, institute_id)
             SELECT ?, ?, ?, roles.id, ?
             FROM roles
             WHERE roles.name = 'institute_admin'"
        );
        $createAdmin->execute([
            $adminName,
            $adminEmail,
            password_hash($adminPassword, PASSWORD_DEFAULT),
            $instituteId
        ]);
        if ($createAdmin->rowCount() !== 1) {
            $pdo->rollBack();
            manageJsonReply(500, false, 'Institute administrator role is missing. Run the migrations.');
        }
        $pdo->commit();
        manageJsonReply(201, true, 'Institute and institute administrator created.', [
            'institute_id' => $instituteId
        ]);
    }

    if (!is_array($data)
        || !isset($data['learner_id'], $data['is_active'])
        || filter_var($data['learner_id'], FILTER_VALIDATE_INT) === false
        || !in_array($data['is_active'], [true, false, 0, 1, '0', '1'], true)
    ) {
        manageJsonReply(422, false, 'A valid learner and activation status are required.');
    }

    $learnerId = (int)$data['learner_id'];
    $isActive = filter_var($data['is_active'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    if ($learnerId < 1 || $isActive === null) {
        manageJsonReply(422, false, 'A valid learner and activation status are required.');
    }

    $pdo->beginTransaction();
    $findLearnerSql =
        "SELECT learners.id
         FROM learners
         INNER JOIN roles ON roles.id = learners.role_id
         WHERE learners.id = ? AND roles.name = 'learner'";
    $findLearnerParams = [$learnerId];
    if ($isInstituteAdmin) {
        $findLearnerSql .= ' AND learners.institute_id = ?';
        $findLearnerParams[] = (int)($_SESSION['institute_id'] ?? 0);
    }
    $findLearner = $pdo->prepare($findLearnerSql . ' FOR UPDATE');
    $findLearner->execute($findLearnerParams);
    if (!$findLearner->fetch()) {
        $pdo->rollBack();
        manageJsonReply(404, false, 'Learner not found.');
    }

    $updateLearner = $pdo->prepare(
        'UPDATE learners SET is_active = ? WHERE id = ?'
    );
    $updateLearner->execute([$isActive ? 1 : 0, $learnerId]);
    $pdo->commit();

    manageJsonReply(200, true, $isActive ? 'Learner activated.' : 'Learner deactivated.', [
        'learner_id' => $learnerId,
        'is_active' => $isActive
    ]);
} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('[manage_handler] ' . $e->getMessage());
    if ((int)($e->errorInfo[1] ?? 0) === 1062) {
        manageJsonReply(409, false, 'That email address is already registered, or the institute name already exists.');
    }
    manageJsonReply(500, false, 'Could not manage learners. Verify the database is migrated and try again.');
}
