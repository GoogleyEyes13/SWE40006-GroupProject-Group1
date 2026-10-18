<?php
declare(strict_types=1);

require_once '/opt/book-library/database/users.php';

ini_set('session.use_strict_mode', '1');

session_start([
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax',
    'cookie_secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'
]);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function respond(int $code, array $data): never
{
    http_response_code($code);
    echo json_encode($data);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    respond(200, [
        'logged_in' => isset($_SESSION['user_id']),
        'user_id' => $_SESSION['user_id'] ?? null
    ]);
}

if ($method !== 'POST') {
    respond(405, ['error' => 'Method not allowed']);
}

$contentType = strtolower(trim(explode(';', $_SERVER['CONTENT_TYPE'] ?? '')[0]));

if ($contentType !== 'application/json') {
    respond(415, ['error' => 'JSON required']);
}

$raw = file_get_contents('php://input');

if ($raw === false || strlen($raw) > 8192) {
    respond(400, ['error' => 'Invalid request']);
}

$data = json_decode($raw, true);

if (!is_array($data)) {
    respond(400, ['error' => 'Invalid JSON']);
}

$action = $data['action'] ?? '';

if ($action === 'logout') {
    $_SESSION = [];
    session_regenerate_id(true);
    respond(200, ['success' => true]);
}

if (!in_array($action, ['register', 'login'], true)) {
    respond(400, ['error' => 'Invalid action']);
}

$email = $data['email'] ?? null;
$password = $data['password'] ?? null;

if (!is_string($email) || !is_string($password)) {
    respond(400, ['error' => 'Invalid credentials']);
}

try {
    $db = connectToDatabase();

    if ($action === 'register') {
        $userId = registerUser($db, $email, $password);
        $code = 201;
    } else {
        $userId = authenticateUser($db, $email, $password);

        if ($userId === null) {
            respond(401, ['error' => 'Invalid credentials']);
        }

        $code = 200;
    }

    session_regenerate_id(true);
    $_SESSION['user_id'] = $userId;

    respond($code, [
        'success' => true,
        'user_id' => $userId
    ]);

} catch (InvalidArgumentException $e) {
    respond(400, ['error' => $e->getMessage()]);
} catch (PDOException $e) {
    if ($e->getCode() === '23505') {
        respond(409, ['error' => 'Email already registered']);
    }

    error_log($e->getMessage());
    respond(500, ['error' => 'Database error']);
} catch (Throwable $e) {
    error_log($e->getMessage());
    respond(500, ['error' => 'Server error']);
}