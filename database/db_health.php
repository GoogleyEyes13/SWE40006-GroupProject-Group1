<?php
declare(strict_types=1);

require_once __DIR__ . '/connection.php';

try {
    $db = connectToDatabase();

    $result = $db->query('SELECT 1')->fetchColumn();

    if ((int)$result !== 1) {
        throw new RuntimeException('Database query failed');
    }

    echo "RDS PostgreSQL: HEALTHY\n";
    exit(0);

} catch (Throwable $e) {
    echo "RDS PostgreSQL: UNHEALTHY\n";
    exit(1);
}