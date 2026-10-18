<?php
declare(strict_types=1);

require_once __DIR__ . '/connection.php';


function saveBook(PDO $db, array $book): int
{
    $key = $book['key'] ?? '';
    $title = trim((string)($book['title'] ?? ''));

    if (!preg_match('~^/works/OL[0-9]+W$~', $key) || $title === '') {
        throw new InvalidArgumentException('Invalid book information');
    }

    $authors = $book['author_name'] ?? [];

    $author = is_array($authors)
        ? implode(', ', $authors)
        : (string)$authors;

    $sql = "
        INSERT INTO books (
            openlibrary_key,
            title,
            author,
            publication_year,
            cover_id
        )
        VALUES (
            :key,
            :title,
            :author,
            :year,
            :cover
        )
        ON CONFLICT (openlibrary_key)
        DO UPDATE SET
            title = EXCLUDED.title,
            author = EXCLUDED.author,
            publication_year = EXCLUDED.publication_year,
            cover_id = EXCLUDED.cover_id
        RETURNING id
    ";

    $stmt = $db->prepare($sql);

    $stmt->execute([
        'key' => $key,
        'title' => $title,
        'author' => $author,
        'year' => $book['first_publish_year'] ?? null,
        'cover' => $book['cover_i'] ?? null
    ]);

    return (int)$stmt->fetchColumn();
}

function getBook(PDO $db, string $key): ?array
{
    $stmt = $db->prepare(
        "SELECT * FROM books WHERE openlibrary_key = :key"
    );

    $stmt->execute(['key' => $key]);

    return $stmt->fetch() ?: null;
}