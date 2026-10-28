<?php
require_once __DIR__ . '/../includes/database.php';

header('Content-Type: text/plain');

try {
    $pdo = getConnection();

    $pdo->exec("CREATE TABLE IF NOT EXISTS migrations (
        id        INT AUTO_INCREMENT PRIMARY KEY,
        migration VARCHAR(255) NOT NULL UNIQUE,
        ran_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $alreadyRan = $pdo->query("SELECT migration FROM migrations")
                      ->fetchAll(PDO::FETCH_COLUMN);

    $files = glob(__DIR__ . '/*.sql');
    sort($files);

    $count = 0;
    foreach ($files as $file) {
        $name = basename($file);

        if (in_array($name, $alreadyRan)) {
            echo "Skipped (already done): $name\n";
            continue;
        }

        $sql = file_get_contents($file);
        if ($sql === false) {
            throw new RuntimeException("Could not read migration: $name");
        }
        if (trim($sql) === '') {
            echo "Skipped (empty migration): $name\n";
            continue;
        }

        $pdo->exec($sql);

        $stmt = $pdo->prepare("INSERT INTO migrations (migration) VALUES (?)");
        $stmt->execute([$name]);

        echo "Migrated: $name\n";
        $count++;
    }

    echo "\nDone. $count new migration(s) ran.\n";

} catch (PDOException $e) {
    error_log('[migration] ' . $e->getMessage());
    http_response_code(500);
    echo "Migration failed. Check the PHP error log for details.\n";
    exit(1);
}