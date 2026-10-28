<?php
// Sends the logged-in user's profile as JSON.
// dashboard.php calls this with fetch() when the page loads.

session_start();
ini_set('display_errors', '0');   // never print PHP errors into the JSON
header('Content-Type: application/json; charset=utf-8');

// Helper: send a JSON reply and stop
function jsonReply($status, $success, $message, $extra = [])
{
    http_response_code($status);
    echo json_encode(array_merge(
        ['success' => $success, 'message' => $message],
        $extra
    ));
    exit;
}

// 1. Only logged-in users may use this API
if (empty($_SESSION['user_id']) && empty($_SESSION['learner_id'])) {
    jsonReply(401, false, 'Your session has expired. Please log in again.');
}

// 2. This API only reads data, so only GET is allowed
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonReply(405, false, 'Method not allowed. Use GET.');
}

require_once __DIR__ . '/../includes/database.php';

// 3. Read the learner from the database
try {
    $pdo = getConnection();

    $userId = $_SESSION['user_id'] ?? $_SESSION['learner_id'];
    $stmt = $pdo->prepare(
        "SELECT learners.full_name, learners.email, learners.created_at,
                learners.is_active, roles.name AS role_name
         FROM learners
         INNER JOIN roles ON roles.id = learners.role_id
         WHERE learners.id = ?"
    );
    $stmt->execute([$userId]);
    $learner = $stmt->fetch();

    if (!$learner) {
        jsonReply(404, false, 'Learner not found.');
    }
    if (!(bool)$learner['is_active']) {
        $_SESSION = [];
        session_destroy();
        jsonReply(401, false, 'Access revoked. Your account has been deactivated. Contact your administrator to restore access.', [
            'code' => 'account_deactivated'
        ]);
    }

    // Make the date easy to read, e.g. "28 Oct 2026"
    $memberSince = date('d M Y', strtotime($learner['created_at']));

    jsonReply(200, true, 'OK', [
        'learner' => [
            'full_name'    => $learner['full_name'],
            'email'        => $learner['email'],
            'member_since' => $memberSince,
            'role'         => ucwords(str_replace('_', ' ', $learner['role_name']))
        ]
    ]);

} catch (PDOException $e) {
    error_log('[dashboard_handler] ' . $e->getMessage());
    jsonReply(500, false, 'Server error. Please try again.');
}