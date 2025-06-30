<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../config/database.php';
require_once '../src/Auth.php';
require_once '../src/Response.php';
require_once '../src/Quiz.php';

use SmartSteps\Auth;
use SmartSteps\Response;
use SmartSteps\Quiz;

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$database = new Database();
$db = $database->getConnection();
$auth = new Auth();
$response = new Response($db);
$quiz = new Quiz($db);

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

$responses = $response->getByQuiz($quiz_id, $teacher->id);

if ($responses === false) {
    http_response_code(404);
    echo json_encode(['error' => 'Quiz not found or access denied']);
    exit;
}

// Get quiz info
$quiz_info = $quiz->getByShareId('', true); // We'll get it differently
$query = "SELECT title, subject, time_limit FROM quizzes WHERE id = :quiz_id AND teacher_id = :teacher_id";
$stmt = $db->prepare($query);
$stmt->bindParam(":quiz_id", $quiz_id);
$stmt->bindParam(":teacher_id", $teacher->id);
$stmt->execute();
$quiz_info = $stmt->fetch();

if (!$quiz_info) {
    http_response_code(404);
    echo json_encode(['error' => 'Quiz not found or access denied']);
    exit;
}

// Count questions
$question_query = "SELECT COUNT(*) as count FROM quiz_questions WHERE quiz_id = :quiz_id";
$question_stmt = $db->prepare($question_query);
$question_stmt->bindParam(":quiz_id", $quiz_id);
$question_stmt->execute();
$question_count = $question_stmt->fetch()['count'];

echo json_encode([
    'quiz' => [
        'title' => $quiz_info['title'],
        'subject' => $quiz_info['subject'],
        'totalQuestions' => $question_count,
        'timeLimit' => $quiz_info['time_limit']
    ],
    'responses' => $responses
]);
?>