<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../config/database.php';
require_once '../src/Auth.php';

use SmartSteps\Auth;

$auth = new Auth();
$teacher = $auth->authenticateRequest();

if (!$teacher) {
    http_response_code(401);
    echo json_encode(['error' => 'Access denied. No valid token provided.']);
    exit;
}

echo json_encode([
    'authenticated' => true,
    'teacher' => $teacher
]);
?>