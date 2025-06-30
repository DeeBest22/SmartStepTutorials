<?php
require_once 'config/database.php';

// Initialize database and create tables
$database = new Database();
$db = $database->getConnection();

if ($db) {
    $database->createTables();
}

// Route handling
$request_uri = $_SERVER['REQUEST_URI'];
$path = parse_url($request_uri, PHP_URL_PATH);

// Remove query parameters and trailing slashes
$path = rtrim($path, '/');

// Define routes
$routes = [
    '' => 'public/home.html',
    '/' => 'public/home.html',
    '/teacher-register' => 'public/teacher-register.html',
    '/teacher-login' => 'public/teacher-login.html',
    '/teacher-dashboard' => 'public/teacher-dashboard.html',
    '/create-quiz' => 'public/create-quiz.html',
    '/quiz-results' => 'public/quiz-results.html',
    '/student-details' => 'public/student-details.html',
    '/quiz-correction' => 'public/quiz-correction.html',
    '/student-quiz' => 'public/student-quiz.html'
];

// Handle dynamic routes
if (preg_match('/^\/quiz\/([a-f0-9-]+)$/', $path, $matches)) {
    include 'public/student-quiz.html';
    exit;
}

if (preg_match('/^\/correction\/([a-f0-9-]+)$/', $path, $matches)) {
    include 'public/quiz-correction.html';
    exit;
}

if (preg_match('/^\/quiz-results\/(\d+)$/', $path, $matches)) {
    include 'public/quiz-results.html';
    exit;
}

if (preg_match('/^\/student-details\/(\d+)$/', $path, $matches)) {
    include 'public/student-details.html';
    exit;
}

// Handle API routes
if (strpos($path, '/api/') === 0) {
    $api_path = substr($path, 4); // Remove '/api'
    
    switch ($api_path) {
        case '/register':
            include 'api/register.php';
            break;
        case '/login':
            include 'api/login.php';
            break;
        case '/logout':
            include 'api/logout.php';
            break;
        case '/verify':
            include 'api/verify.php';
            break;
        case '/quiz':
        case (preg_match('/^\/quiz\/(\d+)$/', $api_path) ? true : false):
            include 'api/quiz.php';
            break;
        case (preg_match('/^\/quiz\/([a-f0-9-]+)$/', $api_path) ? true : false):
            include 'api/quiz-public.php';
            break;
        case (preg_match('/^\/submit\/([a-f0-9-]+)$/', $api_path) ? true : false):
            include 'api/submit.php';
            break;
        case (preg_match('/^\/correction\/([a-f0-9-]+)$/', $api_path) ? true : false):
            include 'api/correction.php';
            break;
        case (preg_match('/^\/responses\/(\d+)$/', $api_path) ? true : false):
            include 'api/responses.php';
            break;
        case '/all-responses':
            include 'api/all-responses.php';
            break;
        case (preg_match('/^\/quiz-stats\/(\d+)$/', $api_path) ? true : false):
            include 'api/quiz-stats.php';
            break;
        case (preg_match('/^\/student-details\/(\d+)$/', $api_path) ? true : false):
            include 'api/student-details.php';
            break;
        default:
            http_response_code(404);
            echo json_encode(['error' => 'API endpoint not found']);
            break;
    }
    exit;
}

// Handle static routes
if (isset($routes[$path])) {
    if (file_exists($routes[$path])) {
        include $routes[$path];
    } else {
        http_response_code(404);
        echo "Page not found";
    }
} else {
    // Check if it's a static file request
    $file_path = 'public' . $path;
    if (file_exists($file_path) && is_file($file_path)) {
        // Serve static files
        $mime_type = mime_content_type($file_path);
        header('Content-Type: ' . $mime_type);
        readfile($file_path);
    } else {
        http_response_code(404);
        echo "Page not found";
    }
}
?>