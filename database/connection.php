<?php
declare(strict_types=1);

function connectToDatabase(): PDO
{
    $host = getenv('DB_HOST');
    $port = getenv('DB_PORT') ?: '5432';
    $name = getenv('DB_NAME');
    $user = getenv('DB_USER');
    $password = getenv('DB_PASSWORD');

    if (!$host || !$name || !$user || !$password) {
        throw new RuntimeException('Missing database configuration');
    }

    $dsn = "pgsql:host=$host;port=$port;dbname=$name;sslmode=require";

    return new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
}