<?php
$surveyType = ($_GET['type'] ?? '360') === '180' ? '180' : '360';
$initialView = 'survey360';
require __DIR__ . '/dashboard.php';