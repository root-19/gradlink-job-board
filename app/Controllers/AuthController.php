<?php
namespace App\Controllers; // ✅ Use correct namespace (matching your folder)

require_once __DIR__ . '/../../vendor/autoload.php'; // ✅ Include autoloader

use App\Config\Database;
use App\Models\User;

class AuthController {
    private $user;
    private $db;

    public function __construct() {
        $this->db = new Database();
        $conn = $this->db->connect(); // ✅ Correct way to use Database
        $this->user = new User($conn);

    }
    public function getUser() {
        return $this->user;
    }

    public function register($firstName, $lastName, $email, $password, $role) {
        $register = $this->user->register($firstName, $lastName, $email, $password, $role);
        
        if ($register) {
            return ["success" => true, "message" => "Registration successful!"];
        } else {
            return ["success" => false, "message" => "Error registering user."];
        }
    }

    public function login($email, $password) {
        $user = $this->user->findUserByEmail($email);
    
        if (!$user) {
            return ["success" => false, "message" => "User not found."];
        }
    
        if (!password_verify($password, $user['password'])) {
            return ["success" => false, "message" => "Incorrect password."];
        }
    
        if ($user['is_verified'] == 0) {
            return ["success" => false, "message" => "Please verify your email."];
        }
    
        session_start();
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
    
        if ($user['role'] == "job_seeker") {
            header("Location: ../Views/job_seeker/Dashboard.php");
        } else {
            header("Location: ../Views/employer/Dashboard.php");
        }
    
        return ["success" => true, "message" => "Login successful!"];
    }
  
    public function verifyCode($email, $code) {
        $user = $this->user->findUserByEmail($email);
    
        if (!$user) {
            return ["success" => false, "message" => "User not found."];
        }
    
        if ($user['verification_code'] == $code) {
            if ($this->user->updateVerificationStatus($email)) {
                return ["success" => true, "message" => "Email verified successfully!"];
            } else {
                return ["success" => false, "message" => "Failed to verify email."];
            }
        } else {
            return ["success" => false, "message" => "Invalid verification code."];
        }
    }
    
}