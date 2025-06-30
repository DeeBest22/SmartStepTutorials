<?php
namespace SmartSteps;

use Ramsey\Uuid\Uuid;

class Response {
    private $conn;
    private $table_name = "student_responses";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create($data) {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET student_name=:student_name, quiz_id=:quiz_id, answers=:answers, 
                      score=:score, total_questions=:total_questions, time_spent=:time_spent, 
                      correction_id=:correction_id";

        $stmt = $this->conn->prepare($query);
        $correction_id = Uuid::uuid4()->toString();

        $stmt->bindParam(":student_name", $data['student_name']);
        $stmt->bindParam(":quiz_id", $data['quiz_id']);
        $stmt->bindParam(":answers", json_encode($data['answers']));
        $stmt->bindParam(":score", $data['score']);
        $stmt->bindParam(":total_questions", $data['total_questions']);
        $stmt->bindParam(":time_spent", $data['time_spent']);
        $stmt->bindParam(":correction_id", $correction_id);

        if ($stmt->execute()) {
            return $correction_id;
        }

        return false;
    }

    public function getByQuiz($quiz_id, $teacher_id) {
        $query = "SELECT sr.*, q.title as quiz_title, q.subject as quiz_subject 
                  FROM " . $this->table_name . " sr 
                  JOIN quizzes q ON sr.quiz_id = q.id 
                  WHERE sr.quiz_id = :quiz_id AND q.teacher_id = :teacher_id 
                  ORDER BY sr.submitted_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":quiz_id", $quiz_id);
        $stmt->bindParam(":teacher_id", $teacher_id);
        $stmt->execute();

        $responses = $stmt->fetchAll();
        
        // Decode JSON answers
        foreach ($responses as &$response) {
            $response['answers'] = json_decode($response['answers'], true);
        }

        return $responses;
    }

    public function getAllByTeacher($teacher_id) {
        $query = "SELECT sr.*, q.title as quiz_title, q.subject as quiz_subject, q.id as quiz_id 
                  FROM " . $this->table_name . " sr 
                  JOIN quizzes q ON sr.quiz_id = q.id 
                  WHERE q.teacher_id = :teacher_id 
                  ORDER BY sr.submitted_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":teacher_id", $teacher_id);
        $stmt->execute();

        $responses = $stmt->fetchAll();
        
        // Group by quiz and decode JSON answers
        $grouped = [];
        foreach ($responses as $response) {
            $response['answers'] = json_decode($response['answers'], true);
            $quiz_id = $response['quiz_id'];
            
            if (!isset($grouped[$quiz_id])) {
                $grouped[$quiz_id] = [
                    'quiz' => [
                        '_id' => $quiz_id,
                        'title' => $response['quiz_title'],
                        'subject' => $response['quiz_subject']
                    ],
                    'responses' => []
                ];
            }
            
            $grouped[$quiz_id]['responses'][] = $response;
        }

        return $grouped;
    }

    public function getByCorrectionId($correction_id) {
        $query = "SELECT sr.*, q.title as quiz_title, q.subject as quiz_subject 
                  FROM " . $this->table_name . " sr 
                  JOIN quizzes q ON sr.quiz_id = q.id 
                  WHERE sr.correction_id = :correction_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":correction_id", $correction_id);
        $stmt->execute();

        $response = $stmt->fetch();
        
        if ($response) {
            $response['answers'] = json_decode($response['answers'], true);
        }

        return $response;
    }

    public function getById($response_id, $teacher_id) {
        $query = "SELECT sr.*, q.title as quiz_title, q.subject as quiz_subject 
                  FROM " . $this->table_name . " sr 
                  JOIN quizzes q ON sr.quiz_id = q.id 
                  WHERE sr.id = :response_id AND q.teacher_id = :teacher_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":response_id", $response_id);
        $stmt->bindParam(":teacher_id", $teacher_id);
        $stmt->execute();

        $response = $stmt->fetch();
        
        if ($response) {
            $response['answers'] = json_decode($response['answers'], true);
        }

        return $response;
    }

    public function getQuizStats($quiz_id, $teacher_id) {
        $query = "SELECT 
                    COUNT(*) as total_attempts,
                    AVG((score / total_questions) * 100) as average_score,
                    MAX((score / total_questions) * 100) as highest_score,
                    MIN((score / total_questions) * 100) as lowest_score,
                    AVG(time_spent) as average_time
                  FROM " . $this->table_name . " sr 
                  JOIN quizzes q ON sr.quiz_id = q.id 
                  WHERE sr.quiz_id = :quiz_id AND q.teacher_id = :teacher_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":quiz_id", $quiz_id);
        $stmt->bindParam(":teacher_id", $teacher_id);
        $stmt->execute();

        $stats = $stmt->fetch();
        
        return [
            'totalAttempts' => (int)$stats['total_attempts'],
            'averageScore' => round($stats['average_score'] ?? 0),
            'highestScore' => round($stats['highest_score'] ?? 0),
            'lowestScore' => round($stats['lowest_score'] ?? 0),
            'averageTime' => round($stats['average_time'] ?? 0)
        ];
    }
}
?>