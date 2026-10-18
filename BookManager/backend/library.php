<?php
declare(strict_types=1);

require_once '/opt/book-library/database/books.php';
require_once '/opt/book-library/database/library.php';
require_once '/opt/book-library/database/session.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function libraryResponse(int $code, array $data): never
{
    http_response_code($code);
    echo json_encode($data);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    libraryResponse(401, ['error' => 'Authentication required']);
}

$userId = (int)$_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

try {
    $db = connectToDatabase();

    if ($method === 'GET') {
        libraryResponse(200, [
            'books' => getUserLibrary($db, $userId)
        ]);
    }

    if ($method !== 'POST') {
        libraryResponse(405, ['error' => 'Method not allowed']);
    }

    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

    if (!is_string($token) || !hash_equals($_SESSION['csrf_token'], $token)) {
        libraryResponse(403, ['error' => 'Invalid security token']);
    }

    $contentType = strtolower(trim(explode(';', $_SERVER['CONTENT_TYPE'] ?? '')[0]));

    if ($contentType !== 'application/json') {
        libraryResponse(415, ['error' => 'JSON required']);
    }

    $raw = file_get_contents('php://input');

    if ($raw === false || strlen($raw) > 16384) {
        libraryResponse(400, ['error' => 'Invalid request']);
    }

    $data = json_decode($raw, true);

    if (!is_array($data)) {
        libraryResponse(400, ['error' => 'Invalid JSON']);
    }

    $action = $data['action'] ?? '';

    if ($action === 'remove') {
        $bookId = $data['book_id'] ?? null;

        if (!is_int($bookId) || $bookId <= 0) {
            libraryResponse(400, ['error' => 'Invalid book ID']);
        }

        $removed = removeUserBook($db, $userId, $bookId);

        libraryResponse($removed ? 200 : 404, [
            'success' => $removed
        ]);
    }

    if (!in_array($action, ['add', 'update'], true)) {
        libraryResponse(400, ['error' => 'Invalid action']);
    }

    $status = $data['status'] ?? 'want_to_read';
    $quantity = $data['quantity'] ?? 0;

    $allowed = ['want_to_read', 'owned', 'reading', 'read'];

    if (!is_string($status) ||
        !in_array($status, $allowed, true) ||
        !is_int($quantity) ||
        $quantity < 0 ||
        $quantity > 1000) {
        libraryResponse(400, ['error' => 'Invalid status or quantity']);
    }

    if ($action === 'update') {
        $bookId = $data['book_id'] ?? null;

        if (!is_int($bookId) || $bookId <= 0) {
            libraryResponse(400, ['error' => 'Invalid book ID']);
        }

        $updated = updateUserBook(
            $db,
            $userId,
            $bookId,
            $status,
            $quantity
        );

        libraryResponse($updated ? 200 : 404, [
            'success' => $updated
        ]);
    }

    $book = $data['book'] ?? null;

    if (!is_array($book) ||
        !is_string($book['key'] ?? null) ||
        !is_string($book['title'] ?? null) ||
        strlen($book['title']) > 500) {
        libraryResponse(400, ['error' => 'Invalid book information']);
    }

    $db->beginTransaction();

    try {
        $bookId = saveBook($db, $book);

        saveUserBook(
            $db,
            $userId,
            $bookId,
            $status,
            $quantity
        );

        $db->commit();
    } catch (Throwable $e) {
        $db->rollBack();
        throw $e;
    }

    libraryResponse(201, [
        'success' => true,
        'book_id' => $bookId
    ]);

} catch (InvalidArgumentException $e) {
    libraryResponse(400, ['error' => $e->getMessage()]);
} catch (Throwable $e) {
    error_log($e->getMessage());
    libraryResponse(500, ['error' => 'Server error']);
}