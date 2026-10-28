<?php
session_start();

if (!empty($_SESSION['user_id']) || !empty($_SESSION['learner_id'])) {
    header('Location: dashboard.php');
} else {
    header('Location: login.php');
}
exit;
