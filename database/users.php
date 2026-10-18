<?php
declare(strict_types=1);

require_once __DIR__ . '/connection.php';


function registerUser(PDO $db, string $email, string $password): int
{
    $email = strtolower(trim($email));

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new InvalidArgumentException('Invalid email address');
    }

    if (strlen($password) < 12) {
        throw new InvalidArgumentException('Password must contain at least 12 characters');
    }

    $stmt = $db->prepare("
        INSERT INTO users (email, password_hash)
        VALUES (:email, :password)
        RETURNING id
    ");

    $stmt->execute([
        'email' => $email,
        'password' => password_hash($password, PASSWORD_DEFAULT)
    ]);

    return (int)$stmt->fetchColumn();
}


function authenticateUser(PDO $db, string $email, string $password): ?int
{
    $stmt = $db->prepare("
        SELECT id, password_hash
        FROM users
        WHERE email = :email
    ");

    $stmt->execute([
        'email' => strtolower(trim($email))
    ]);

    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        return null;
    }

    return (int)$user['id'];
}