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

// Get response_id from URL
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path_parts = explode('/', $path);
$response_id = end($path_parts);

if (!$response_id || !is_numeric($response_id)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid response ID']);
    exit;
}

$response_data = $response->getById($response_id, $teacher->id);

if (!$response_data) {
    http_response_code(404);
    echo json_encode(['error' => 'Response not found or access denied']);
    exit;
}

// Get quiz questions with correct answers
$query = "SELECT qq.question, qq.option_a, qq.option_b, qq.option_c, qq.option_d, qq.correct_answer 
          FROM quiz_questions qq 
          WHERE qq.quiz_id = :quiz_id 
          ORDER BY qq.question_order";

$stmt = $db->prepare($query);
$stmt->bindParam(":quiz_id", $response_data['quiz_id']);
$stmt->execute();
$questions = $stmt->fetchAll();

// Create question analysis
$question_analysis = [];
foreach ($questions as $index => $question) {
    $student_answer = $response_data['answers'][$index] ?? -1;
    $correct_answer = $question['correct_answer'];
    
    $question_analysis[] = [
        'question' => $question['question'],
        'options' => [
            $question['option_a'],
            $question['option_b'],
            $question['option_c'],
            $question['option_d']
        ],
        'correctAnswer' => $correct_answer,
        'studentAnswer' => $student_answer,
        'isCorrect' => $student_answer === $correct_answer
    ];
}

$detailed_response = [
    'studentName' => $response_data['student_name'],
    'quiz' => [
        'title' => $response_data['quiz_title'],
        'subject' => $response_data['quiz_subject']
    ],
    'score' => $response_data['score'],
    'totalQuestions' => $response_data['total_questions'],
    'percentage' => round(($response_data['score'] / $response_data['total_questions']) * 100),
    'timeSpent' => $response_data['time_spent'],
    'submittedAt' => $response_data['submitted_at'],
    'questionAnalysis' => $question_analysis
];

echo json_encode($detailed_response);
?>