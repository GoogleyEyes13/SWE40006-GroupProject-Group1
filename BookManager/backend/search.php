<?php
namespace Search;

header('Content-Type: application/json; charset=utf-8');

$query = trim($_GET['q'] ?? '');

if ($query === '' || strlen($query) > 120) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid search query']);
    exit;
}

$url = 'https://openlibrary.org/search.json?' . http_build_query([
    'q' => $query,
    'fields' => 'key,title,author_name,first_publish_year,cover_i',
    'limit' => 10
]);

$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => "User-Agent: BookManager-UniversityProject/1.0\r\nAccept: application/json\r\n",
        'timeout' => 10
    ]
]);

$response = @file_get_contents($url, false, $context);

if ($response === false) {
    http_response_code(502);
    echo json_encode(['error' => 'Open Library unavailable']);
    exit;
}

echo $response;