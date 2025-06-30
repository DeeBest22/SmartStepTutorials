<?php
namespace SmartSteps;

use Ramsey\Uuid\Uuid;

class Quiz {
    private $conn;
    private $table_name = "quizzes";
    private $questions_table = "quiz_questions";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create($data) {
        $this->conn->beginTransaction();

        try {
            // Insert quiz
            $query = "INSERT INTO " . $this->table_name . " 
                      SET title=:title, subject=:subject, teacher_id=:teacher_id, 
                          time_limit=:time_limit, share_id=:share_id";

            $stmt = $this->conn->prepare($query);
            $share_id = Uuid::uuid4()->toString();

            $stmt->bindParam(":title", $data['title']);
            $stmt->bindParam(":subject", $data['subject']);
            $stmt->bindParam(":teacher_id", $data['teacher_id']);
            $stmt->bindParam(":time_limit", $data['time_limit']);
            $stmt->bindParam(":share_id", $share_id);

            $stmt->execute();
            $quiz_id = $this->conn->lastInsertId();

            // Insert questions
            $question_query = "INSERT INTO " . $this->questions_table . " 
                               SET quiz_id=:quiz_id, question=:question, option_a=:option_a, 
                                   option_b=:option_b, option_c=:option_c, option_d=:option_d, 
                                   correct_answer=:correct_answer, question_order=:question_order";

            $question_stmt = $this->conn->prepare($question_query);

            foreach ($data['questions'] as $index => $question) {
                $question_stmt->bindParam(":quiz_id", $quiz_id);
                $question_stmt->bindParam(":question", $question['question']);
                $question_stmt->bindParam(":option_a", $question['options'][0]);
                $question_stmt->bindParam(":option_b", $question['options'][1]);
                $question_stmt->bindParam(":option_c", $question['options'][2]);
                $question_stmt->bindParam(":option_d", $question['options'][3]);
                $question_stmt->bindParam(":correct_answer", $question['correctAnswer']);
                $question_stmt->bindParam(":question_order", $index);

                $question_stmt->execute();
            }

            $this->conn->commit();
            return ['id' => $quiz_id, 'share_id' => $share_id];

        } catch (Exception $e) {
            $this->conn->rollback();
            return false;
        }
    }

    public function getByTeacher($teacher_id) {
        $query = "SELECT q.*, COUNT(qqs.id) as question_count 
                  FROM " . $this->table_name . " q 
                  LEFT JOIN " . $this->questions_table . " qqs ON q.id = qqs.quiz_id 
                  WHERE q.teacher_id = :teacher_id 
                  GROUP BY q.id 
                  ORDER BY q.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":teacher_id", $teacher_id);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function getByShareId($share_id, $include_answers = false) {
        $query = "SELECT q.*, qq.id as question_id, qq.question, qq.option_a, qq.option_b, 
                         qq.option_c, qq.option_d" . 
                         ($include_answers ? ", qq.correct_answer" : "") . "
                  FROM " . $this->table_name . " q 
                  LEFT JOIN " . $this->questions_table . " qq ON q.id = qq.quiz_id 
                  WHERE q.share_id = :share_id 
                  ORDER BY qq.question_order";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":share_id", $share_id);
        $stmt->execute();

        $results = $stmt->fetchAll();
        
        if (empty($results)) {
            return null;
        }

        // Format the response
        $quiz = [
            'id' => $results[0]['id'],
            'title' => $results[0]['title'],
            'subject' => $results[0]['subject'],
            'teacher_id' => $results[0]['teacher_id'],
            'time_limit' => $results[0]['time_limit'],
            'timeLimit' => $results[0]['time_limit'], // For frontend compatibility
            'share_id' => $results[0]['share_id'],
            'created_at' => $results[0]['created_at'],
            'questions' => []
        ];

        foreach ($results as $row) {
            if ($row['question_id']) {
                $question = [
                    'question' => $row['question'],
                    'options' => [
                        $row['option_a'],
                        $row['option_b'],
                        $row['option_c'],
                        $row['option_d']
                    ]
                ];

                if ($include_answers) {
                    $question['correctAnswer'] = $row['correct_answer'];
                }

                $quiz['questions'][] = $question;
            }
        }

        return $quiz;
    }

    public function delete($quiz_id, $teacher_id) {
        // Verify ownership
        $query = "SELECT id FROM " . $this->table_name . " 
                  WHERE id = :quiz_id AND teacher_id = :teacher_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":quiz_id", $quiz_id);
        $stmt->bindParam(":teacher_id", $teacher_id);
        $stmt->execute();

        if ($stmt->rowCount() === 0) {
            return false;
        }

        // Delete quiz (cascade will handle questions and responses)
        $delete_query = "DELETE FROM " . $this->table_name . " 
                         WHERE id = :quiz_id AND teacher_id = :teacher_id";
        
        $delete_stmt = $this->conn->prepare($delete_query);
        $delete_stmt->bindParam(":quiz_id", $quiz_id);
        $delete_stmt->bindParam(":teacher_id", $teacher_id);

        return $delete_stmt->execute();
    }
}
?>