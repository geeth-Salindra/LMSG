<?php
session_start();

// Remove all saved session data
$_SESSION = [];
session_destroy();

// Send the learner back to the login page
header('Location: ../login.php');
exit;