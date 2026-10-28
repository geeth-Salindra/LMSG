<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user_id']) && empty($_SESSION['learner_id'])) {
    header('Location: login.php');
    exit;
}

if (($_SESSION['user_role'] ?? '') !== 'super_admin') {
    header('Location: dashboard.php');
    exit;
}

$initialView = 'manage';
require __DIR__ . '/dashboard.php';
