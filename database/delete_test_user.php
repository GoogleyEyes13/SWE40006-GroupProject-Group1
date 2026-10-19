<?php
declare(strict_types=1);

require_once __DIR__ . '/connection.php';

$email = strtolower(trim($argv[1] ?? ''));

if (!str_ends_with($email, '@example.test')) {
    fwrite(STDERR, "Only @example.test accounts can be deleted\n");
    exit(1);
}

$db = connectToDatabase();

$stmt = $db->prepare('DELETE FROM users WHERE email = :email');
$stmt->execute(['email' => $email]);

echo "Deleted {$stmt->rowCount()} test account(s)\n";
