<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../config/database.php';
require_once '../src/Auth.php';
require_once '../src/Response.php';

use SmartSteps\Auth;
use SmartSteps\Response;

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$database = new Database();
$db = $database->getConnection();
$auth = new Auth();
$response = new Response($db);

// Authentication required
$teacher = $auth->authenticateRequest();
if (!$teacher) {
    http_response_code(401);
    echo json_encode(['error' => 'Access denied. No valid token provided.']);
    exit;
}

// Get quiz_id from URL
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path_parts = explode('/', $path);
$quiz_id = end($path_parts);

if (!$quiz_id || !is_numeric($quiz_id)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid quiz ID']);
    exit;
}

$stats = $response->getQuizStats($quiz_id, $teacher->id);

echo json_encode($stats);
?>