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

$grouped_responses = $response->getAllByTeacher($teacher->id);

echo json_encode($grouped_responses);
?>