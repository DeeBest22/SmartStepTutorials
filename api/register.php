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

if (!$data || !isset($data['name'], $data['email'], $data['password'], $data['subject'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

// Validate password confirmation
if (isset($data['confirmPassword']) && $data['password'] !== $data['confirmPassword']) {
    http_response_code(400);
    echo json_encode(['error' => 'Passwords do not match']);
    exit;
}

// Check if teacher already exists
if ($teacher->emailExists($data['email'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Teacher with this email already exists']);
    exit;
}

// Hash password
$hashed_password = $auth->hashPassword($data['password']);

// Create teacher
$teacher_data = [
    'name' => $data['name'],
    'email' => $data['email'],
    'password' => $hashed_password,
    'subject' => $data['subject']
];

$teacher_id = $teacher->register($teacher_data);

if ($teacher_id) {
    // Generate JWT token
    $token_data = [
        'id' => $teacher_id,
        'email' => $data['email'],
        'name' => $data['name'],
        'subject' => $data['subject']
    ];
    
    $token = $auth->generateToken($token_data);
    
    // Set cookie
    setcookie('token', $token, time() + (24 * 60 * 60), '/', '', false, true);
    
    echo json_encode([
        'message' => 'Teacher registered successfully',
        'teacher' => $token_data
    ]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Server error during registration']);
}
?>