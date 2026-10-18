<?php
declare(strict_types=1);

require_once __DIR__ . '/users.php';
require_once __DIR__ . '/books.php';
require_once __DIR__ . '/library.php';

$db = connectToDatabase();

try {

    $db->beginTransaction();

    $email = 'test-' . bin2hex(random_bytes(6)) . '@example.test';
    $password = bin2hex(random_bytes(16));

    $userId = registerUser($db, $email, $password);

    echo "PASS: User created\n";


    $loginId = authenticateUser($db, $email, $password);

    if ($loginId !== $userId) {
        throw new RuntimeException('Authentication failed');
    }

    echo "PASS: Authentication\n";


    $book = getBook($db, '/works/OL27448W');

    if (!$book) {
        throw new RuntimeException('Sample book not found');
    }

    $bookId = (int)$book['id'];


    saveUserBook($db, $userId, $bookId, 'want_to_read', 0);

    echo "PASS: Book added to wishlist\n";


    saveUserBook($db, $userId, $bookId, 'owned', 1);

    echo "PASS: Book status updated\n";


    $library = getUserLibrary($db, $userId);

    if (count($library) !== 1 ||
        $library[0]['status'] !== 'owned' ||
        (int)$library[0]['quantity'] !== 1) {
        throw new RuntimeException('Library retrieval failed');
    }

    echo "PASS: Personal library retrieved\n";


    if (!removeUserBook($db, $userId, $bookId)) {
        throw new RuntimeException('Book removal failed');
    }

    echo "PASS: Book removed\n";


    $db->rollBack();

    echo "\nALL DATABASE TESTS PASSED!\n";

} finally {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
}