<?php
// This file is used in two ways:
//  1) register.php includes it to get the security token ($csrfToken)
//  2) the form's fetch() sends a POST request to request an institute admin account

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Create one random security token per session (CSRF protection)
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

// Helper: send a JSON reply and stop
function jsonReply($status, $success, $message)
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => $success, 'message' => $message]);
    exit;
}

// ---------- Only run the code below when the form sends a POST ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    ini_set('display_errors', '0');   // never print PHP errors into the JSON

    // CHANGED: database.php now lives in LMSG/includes
    require_once __DIR__ . '/../includes/database.php';

    // 1. Check the security token
    $sentToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $sentToken)) {
        jsonReply(403, false, 'Security token expired. Please refresh the page.');
    }

    // 2. Read the JSON sent by the form
    $data = json_decode(file_get_contents('php://input'), true);
    if (!is_array($data)) {
        jsonReply(422, false, 'A valid registration request is required.');
    }

    $fullName = trim($data['full_name'] ?? '');
    $email    = trim($data['email'] ?? '');
    $password = $data['password'] ?? '';
    $confirm  = $data['confirm_password'] ?? '';

    // 3. Validate
    if ($fullName === '' || $email === '' || $password === '') {
        jsonReply(422, false, 'All fields are required.');
    }
    if (strlen($fullName) > 100) {
        jsonReply(422, false, 'Full name is too long (max 100 characters).');
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonReply(422, false, 'Please enter a valid email address.');
    }
    if (strlen($password) < 6) {
        jsonReply(422, false, 'Password must be at least 6 characters.');
    }
    if ($password !== $confirm) {
        jsonReply(422, false, 'Passwords do not match.');
    }

    // 4. Save to the database
    try {
        $pdo = getConnection();
        $pdo->beginTransaction();

        $instituteCheck = $pdo->query(
            'SELECT id FROM institutes WHERE is_active = 1 ORDER BY id FOR UPDATE'
        );
        $activeInstitutes = $instituteCheck->fetchAll();
        if (count($activeInstitutes) !== 1) {
            $pdo->rollBack();
            jsonReply(409, false, count($activeInstitutes) === 0
                ? 'Registration is unavailable because there is no active institute.'
                : 'Registration is unavailable because more than one active institute is configured.');
        }
        $instituteId = (int)$activeInstitutes[0]['id'];

        // Is this email already registered?
        $check = $pdo->prepare("SELECT id FROM learners WHERE email = ?");
        $check->execute([$email]);
        if ($check->fetch()) {
            $pdo->rollBack();
            jsonReply(409, false, 'This email is already registered.');
        }

        // Never store plain passwords - hash them
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $insert = $pdo->prepare(
            "INSERT INTO learners (full_name, email, password, role_id, institute_id, is_active)
             SELECT ?, ?, ?, roles.id, ?, 0
             FROM roles
             INNER JOIN institutes ON institutes.id = ? AND institutes.is_active = 1
             WHERE roles.name = 'institute_admin'"
        );
        $insert->execute([$fullName, $email, $hash, $instituteId, $instituteId]);
        if ($insert->rowCount() !== 1) {
            $pdo->rollBack();
            error_log('[register_handler] Institute admin role is missing. Run the role migration.');
            jsonReply(500, false, 'Registration is unavailable. Please contact the system administrator.');
        }

        $pdo->commit();
        jsonReply(201, true, 'Your institute admin request was submitted. You can sign in after a Super Admin approves it.');

    } catch (PDOException $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('[register_handler] ' . $e->getMessage());
        jsonReply(500, false, 'Server error. Did you run the migration?');
    }
}