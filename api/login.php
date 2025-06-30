<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';
require_once '../src/Teacher.php';
require_once '../src/Auth.php';

use SmartSteps\Teacher;
use SmartSteps\Auth;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$database = new Database();
$db = $database->getConnection();
$teacher = new Teacher($db);
$auth = new Auth();

$data = json_decode(file_get_contents("php://input"), true);

if (!$data || !isset($data['email'], $data['password'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing email or password']);
    exit;
}

// Find teacher
$teacher_data = $teacher->findByEmail($data['email']);

if (!$teacher_data) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid email or password']);
    exit;
}

// Verify password
if (!$auth->verifyPassword($data['password'], $teacher_data['password'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid email or password']);
    exit;
}

// Generate JWT token
$token_data = [
    'id' => $teacher_data['id'],
    'email' => $teacher_data['email'],
    'name' => $teacher_data['name'],
    'subject' => $teacher_data['subject']
];

$token = $auth->generateToken($token_data);

// Set cookie
setcookie('token', $token, time() + (24 * 60 * 60), '/', '', false, true);

echo json_encode([
    'message' => 'Login successful',
    'teacher' => $token_data
]);
?>