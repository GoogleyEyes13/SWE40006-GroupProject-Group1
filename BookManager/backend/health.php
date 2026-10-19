<?php
header('Content-Type: application/json');

echo json_encode([
    'status' => 'OK',
    'message' => 'PHP backend is working!',
    'environment' => getenv('APP_ENV') ?: 'unknown',
    'version' => getenv('APP_VERSION') ?: 'unknown'
]);