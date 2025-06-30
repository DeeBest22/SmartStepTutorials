<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';
require_once '../src/Quiz.php';

use SmartSteps\Quiz;

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$database = new Database();
$db = $database->getConnection();
$quiz = new Quiz($db);

// Get share_id from URL
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path_parts = explode('/', $path);
$share_id = end($path_parts);

if (!$share_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing share ID']);
    exit;
}

$quiz_data = $quiz->getByShareId($share_id, false); // Don't include correct answers

if (!$quiz_data) {
    http_response_code(404);
    echo json_encode(['error' => 'Quiz not found']);
    exit;
}

echo json_encode($quiz_data);
?>