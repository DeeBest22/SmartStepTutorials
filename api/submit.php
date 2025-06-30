<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';
require_once '../src/Quiz.php';
require_once '../src/Response.php';

use SmartSteps\Quiz;
use SmartSteps\Response;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$database = new Database();
$db = $database->getConnection();
$quiz = new Quiz($db);
$response = new Response($db);

// Get share_id from URL
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path_parts = explode('/', $path);
$share_id = end($path_parts);

$data = json_decode(file_get_contents("php://input"), true);

if (!$data || !isset($data['studentName'], $data['answers'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

// Get quiz with correct answers
$quiz_data = $quiz->getByShareId($share_id, true);

if (!$quiz_data) {
    http_response_code(404);
    echo json_encode(['error' => 'Quiz not found']);
    exit;
}

// Calculate score
$score = 0;
$answers = $data['answers'];

foreach ($quiz_data['questions'] as $index => $question) {
    if (isset($answers[$index]) && $answers[$index] === $question['correctAnswer']) {
        $score++;
    }
}

// Save response
$response_data = [
    'student_name' => $data['studentName'],
    'quiz_id' => $quiz_data['id'],
    'answers' => $answers,
    'score' => $score,
    'total_questions' => count($quiz_data['questions']),
    'time_spent' => $data['timeSpent'] ?? 0
];

$correction_id = $response->create($response_data);

if ($correction_id) {
    $percentage = round(($score / count($quiz_data['questions'])) * 100);
    
    echo json_encode([
        'message' => 'Quiz submitted successfully',
        'score' => $score,
        'totalQuestions' => count($quiz_data['questions']),
        'percentage' => $percentage,
        'correctionId' => $correction_id
    ]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Error submitting quiz']);
}
?>