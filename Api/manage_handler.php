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

if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'super_admin') {
    manageJsonReply(403, false, 'Super Admin access is required.');
}

if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'POST'], true)) {
    header('Allow: GET, POST');
    manageJsonReply(405, false, 'Method not allowed.');
}

require_once __DIR__ . '/../includes/database.php';

try {
    $pdo = getConnection();

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $stmt = $pdo->query(
            "SELECT learners.id, learners.full_name, learners.email,
                    learners.created_at, learners.is_active
             FROM learners
             INNER JOIN roles ON roles.id = learners.role_id
             WHERE roles.name = 'learner'
             ORDER BY learners.full_name, learners.id"
        );

        manageJsonReply(200, true, 'Learners loaded.', [
            'learners' => $stmt->fetchAll()
        ]);
    }

    $sentToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    $sessionToken = $_SESSION['csrf_token'] ?? '';
    if ($sessionToken === '' || !hash_equals($sessionToken, $sentToken)) {
        manageJsonReply(403, false, 'Security token expired. Refresh the page and try again.');
    }

    $data = json_decode(file_get_contents('php://input'), true);
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
    $findLearner = $pdo->prepare(
        "SELECT learners.id
         FROM learners
         INNER JOIN roles ON roles.id = learners.role_id
         WHERE learners.id = ? AND roles.name = 'learner'
         FOR UPDATE"
    );
    $findLearner->execute([$learnerId]);
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
    manageJsonReply(500, false, 'Could not manage learners. Verify the database is migrated and try again.');
}
