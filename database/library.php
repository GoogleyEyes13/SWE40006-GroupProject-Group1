<?php
declare(strict_types=1);

require_once __DIR__ . '/connection.php';


function saveUserBook(
    PDO $db,
    int $userId,
    int $bookId,
    string $status,
    int $quantity
): void {

    $allowed = [
        'want_to_read',
        'owned',
        'reading',
        'read'
    ];

    if (!in_array($status, $allowed, true)) {
        throw new InvalidArgumentException('Invalid reading status');
    }

    if ($quantity < 0) {
        throw new InvalidArgumentException('Quantity cannot be negative');
    }

    $stmt = $db->prepare("
        INSERT INTO user_books (
            user_id,
            book_id,
            status,
            quantity
        )
        VALUES (
            :user_id,
            :book_id,
            :status,
            :quantity
        )
        ON CONFLICT (user_id, book_id)
        DO UPDATE SET
            status = EXCLUDED.status,
            quantity = EXCLUDED.quantity
    ");

    $stmt->execute([
        'user_id' => $userId,
        'book_id' => $bookId,
        'status' => $status,
        'quantity' => $quantity
    ]);
}


function getUserLibrary(PDO $db, int $userId): array
{
    $stmt = $db->prepare("
        SELECT
            b.id,
            b.openlibrary_key,
            b.title,
            b.author,
            b.publication_year,
            b.cover_id,
            ub.status,
            ub.quantity,
            ub.date_added
        FROM user_books ub
        JOIN books b ON ub.book_id = b.id
        WHERE ub.user_id = :user_id
        ORDER BY ub.date_added DESC, b.id DESC
    ");

    $stmt->execute(['user_id' => $userId]);

    return $stmt->fetchAll();
}


function removeUserBook(PDO $db, int $userId, int $bookId): bool
{
    $stmt = $db->prepare("
        DELETE FROM user_books
        WHERE user_id = :user_id
        AND book_id = :book_id
    ");

    $stmt->execute([
        'user_id' => $userId,
        'book_id' => $bookId
    ]);

    return $stmt->rowCount() > 0;
}

function updateUserBook(
    PDO $db,
    int $userId,
    int $bookId,
    string $status,
    int $quantity
): bool {
    $allowed = ['want_to_read', 'owned', 'reading', 'read'];

    if (!in_array($status, $allowed, true) || $quantity < 0 || $quantity > 1000) {
        throw new InvalidArgumentException('Invalid book details');
    }

    $stmt = $db->prepare("
        UPDATE user_books
        SET status = :status, quantity = :quantity
        WHERE user_id = :user_id AND book_id = :book_id
    ");

    $stmt->execute([
        'status' => $status,
        'quantity' => $quantity,
        'user_id' => $userId,
        'book_id' => $bookId
    ]);

    return $stmt->rowCount() > 0;
}