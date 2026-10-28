<?php
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Allow: GET');
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

require_once __DIR__ . '/../includes/database.php';

try {
    $pdo = getConnection();
    $stmt = $pdo->query(
        'SELECT id, name FROM institutes WHERE is_active = 1 ORDER BY name, id'
    );
    echo json_encode(['success' => true, 'institutes' => $stmt->fetchAll()]);
} catch (PDOException $e) {
    error_log('[institutes_handler] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Could not load institutes.']);
}
