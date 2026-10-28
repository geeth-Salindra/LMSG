<?php
$configPath = __DIR__ . '/config.php';
$config = require (is_file($configPath) ? $configPath : __DIR__ . '/config.example.php');
$databaseConfig = $config['database'] ?? [];

$databaseSetting = static function ($environmentVariable, $configKey, $default) use ($databaseConfig) {
    $value = getenv($environmentVariable);

    return $value === false ? ($databaseConfig[$configKey] ?? $default) : $value;
};

define('DB_HOST', $databaseSetting('DB_HOST', 'host', '127.0.0.1'));
define('DB_PORT', $databaseSetting('DB_PORT', 'port', '3306'));
define('DB_NAME', $databaseSetting('DB_NAME', 'name', 'lmsg_db'));
define('DB_USER', $databaseSetting('DB_USER', 'user', 'root'));
define('DB_PASS', $databaseSetting('DB_PASS', 'password', ''));
define('DB_CHARSET', $databaseSetting('DB_CHARSET', 'charset', 'utf8mb4'));

function getConnection()
{
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;

    return new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
}