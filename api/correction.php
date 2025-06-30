<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';
require_once '../src/Response.php';
require_once '../src/Quiz.php';

use SmartSteps\Response;
use SmartSteps\Quiz;

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$database = new Database();
$db = $database->getConnection();
$response = new Response($db);
$quiz = new Quiz($db);

// Get correction_id from URL
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path_parts = explode('/', $path);
$correction_id = end($path_parts);

if (!$correction_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing correction ID']);
    exit;
}

$response_data = $response->getByCorrectionId($correction_id);

if (!$response_data) {
    http_response_code(404);
    echo json_encode(['error' => 'Correction not found']);
    exit;
}

// Get quiz with correct answers
$quiz_data = $quiz->getByShareId($response_data['share_id'] ?? '', true);

if (!$quiz_data) {
    // Fallback: get quiz by ID
    $query = "SELECT q.*, qq.question, qq.option_a, qq.option_b, qq.option_c, qq.option_d, qq.correct_answer 
              FROM quizzes q 
              LEFT JOIN quiz_questions qq ON q.id = qq.quiz_id 
              WHERE q.id = :quiz_id 
              ORDER BY qq.question_order";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(":quiz_id", $response_data['quiz_id']);
    $stmt->execute();
    
    $results = $stmt->fetchAll();
    
    if (!empty($results)) {
        $quiz_data = [
            'id' => $results[0]['id'],
            'title' => $results[0]['title'],
            'subject' => $results[0]['subject'],
            'questions' => []
        ];
        
        foreach ($results as $row) {
            if ($row['question']) {
                $quiz_data['questions'][] = [
                    'question' => $row['question'],
                    'options' => [
                        $row['option_a'],
                        $row['option_b'],
                        $row['option_c'],
                        $row['option_d']
                    ],
                    'correctAnswer' => $row['correct_answer']
                ];
            }
        }
    }
}

$correction_data = [
    'studentName' => $response_data['student_name'],
    'quiz' => $quiz_data,
    'studentAnswers' => $response_data['answers'],
    'score' => $response_data['score'],
    'totalQuestions' => $response_data['total_questions'],
    'percentage' => round(($response_data['score'] / $response_data['total_questions']) * 100),
    'timeSpent' => $response_data['time_spent'],
    'submittedAt' => $response_data['submitted_at']
];

echo json_encode($correction_data);
?>