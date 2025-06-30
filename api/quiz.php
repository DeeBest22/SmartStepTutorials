<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../config/database.php';
require_once '../src/Auth.php';
require_once '../src/Quiz.php';

use SmartSteps\Auth;
use SmartSteps\Quiz;

$database = new Database();
$db = $database->getConnection();
$auth = new Auth();
$quiz = new Quiz($db);

// Authentication required for all quiz operations
$teacher = $auth->authenticateRequest();
if (!$teacher) {
    http_response_code(401);
    echo json_encode(['error' => 'Access denied. No valid token provided.']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'POST':
        // Create quiz
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (!$data || !isset($data['title'], $data['questions'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required fields']);
            exit;
        }

        $quiz_data = [
            'title' => $data['title'],
            'subject' => $teacher->subject,
            'teacher_id' => $teacher->id,
            'time_limit' => $data['timeLimit'] ?? 0,
            'questions' => $data['questions']
        ];

        $result = $quiz->create($quiz_data);
        
        if ($result) {
            echo json_encode([
                'message' => 'Quiz created successfully',
                'quiz' => $result
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Error creating quiz']);
        }
        break;

    case 'GET':
        // Get teacher's quizzes
        $quizzes = $quiz->getByTeacher($teacher->id);
        echo json_encode($quizzes);
        break;

    case 'DELETE':
        // Delete quiz
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $path_parts = explode('/', $path);
        $quiz_id = end($path_parts);

        if (!$quiz_id || !is_numeric($quiz_id)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid quiz ID']);
            exit;
        }

        if ($quiz->delete($quiz_id, $teacher->id)) {
            echo json_encode(['message' => 'Quiz and all associated responses deleted successfully']);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Quiz not found or access denied']);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}
?>