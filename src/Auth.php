<?php
namespace SmartSteps;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class Auth {
    private $secret_key;
    
    public function __construct() {
        $this->secret_key = $_ENV['JWT_SECRET'] ?? 'smart-steps-secret-key';
    }

    public function generateToken($teacher_data) {
        $payload = [
            'iss' => 'smart-steps-tutorial',
            'aud' => 'smart-steps-tutorial',
            'iat' => time(),
            'exp' => time() + (24 * 60 * 60), // 24 hours
            'data' => [
                'id' => $teacher_data['id'],
                'email' => $teacher_data['email'],
                'name' => $teacher_data['name'],
                'subject' => $teacher_data['subject']
            ]
        ];

        return JWT::encode($payload, $this->secret_key, 'HS256');
    }

    public function verifyToken($token) {
        try {
            $decoded = JWT::decode($token, new Key($this->secret_key, 'HS256'));
            return $decoded->data;
        } catch (Exception $e) {
            return false;
        }
    }

    public function hashPassword($password) {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    public function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }

    public function authenticateRequest() {
        $token = null;
        
        // Check for token in cookie
        if (isset($_COOKIE['token'])) {
            $token = $_COOKIE['token'];
        }
        
        // Check for token in Authorization header
        $headers = getallheaders();
        if (isset($headers['Authorization'])) {
            $auth_header = $headers['Authorization'];
            if (preg_match('/Bearer\s+(.*)$/i', $auth_header, $matches)) {
                $token = $matches[1];
            }
        }

        if (!$token) {
            return false;
        }

        return $this->verifyToken($token);
    }
}
?>