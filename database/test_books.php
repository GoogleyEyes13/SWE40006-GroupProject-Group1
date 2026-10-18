<?php
require_once __DIR__ . '/books.php';

$db = connectToDatabase();

$sampleBook = [
    'key' => '/works/OL27448W',
    'title' => 'The Lord of the Rings',
    'author_name' => ['J. R. R. Tolkien'],
    'first_publish_year' => 1954,
    'cover_i' => 258027
];


$id = saveBook($db, $sampleBook);

echo "Book saved successfully!\n";
echo "Book ID: $id\n";


$book = getBook($db, $sampleBook['key']);

echo "\nRetrieved from PostgreSQL:\n";
echo json_encode($book, JSON_PRETTY_PRINT) . "\n";