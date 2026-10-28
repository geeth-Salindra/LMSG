<?php
// This file is used in two ways:
//  1) login.php includes it to get the security token ($csrfToken)
//  2) the form's fetch() sends a POST request to it to log a learner in

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Create one random security token per session (CSRF protection)
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

// Helper: send a JSON reply and stop
// $extra lets us add more data to the reply (like the redirect address)
function jsonReply($status, $success, $message, $extra = [])
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array_merge(
        ['success' => $success, 'message' => $message],
        $extra
    ));
    exit;
}

// ---------- Only run the code below when the form sends a POST ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    ini_set('display_errors', '0');   // never print PHP errors into the JSON

    require_once __DIR__ . '/../includes/database.php';

    // 1. Check the security token
    $sentToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $sentToken)) {
        jsonReply(403, false, 'Security token expired. Please refresh the page.');
    }

    // 2. Read the JSON sent by the form
    $data = json_decode(file_get_contents('php://input'), true);

    $email    = trim($data['email'] ?? '');
    $password = $data['password'] ?? '';

    // 3. Validate
    if ($email === '' || $password === '') {
        jsonReply(422, false, 'Email and password are required.');
    }

    // 4. Find the learner and check the password
    try {
        $pdo = getConnection();

        $stmt = $pdo->prepare(
            "SELECT id, full_name, password FROM learners WHERE email = ?"
        );
        $stmt->execute([$email]);
        $learner = $stmt->fetch();

        // password_verify() compares the typed password with the scrambled one
        if (!$learner || !password_verify($password, $learner['password'])) {
            // Same message for "no such email" and "wrong password" on purpose
            jsonReply(401, false, 'Invalid email or password.');
        }

        // 5. Login success: remember this learner in the session
        session_regenerate_id(true);   // new session ID for safety
        $_SESSION['learner_id']   = $learner['id'];
        $_SESSION['learner_name'] = $learner['full_name'];

        jsonReply(200, true, 'Login successful! Redirecting...', [
            'redirect' => 'dashboard.php'
        ]);

    } catch (PDOException $e) {
        error_log('[login_handler] ' . $e->getMessage());
        $driverError = (int)($e->errorInfo[1] ?? 0);
        if (in_array($driverError, [2002, 2003, 2005], true)) {
            jsonReply(503, false, 'Cannot connect to MySQL. Check that the database service is running and the connection settings are correct.');
        }

        jsonReply(500, false, 'Database query failed. Verify the learners table exists and run the migrations.');
    }
}