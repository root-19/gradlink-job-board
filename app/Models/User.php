<?php
namespace App\Models;

include_once __DIR__ . '/../Config/Database.php';
use App\Config\Database;
use PDO;


//  $userModel = new User();
//  $userId = $_SESSION['user_id'];

class User {
    private $conn;
    private $table = "users";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }

    // ✅ Add this function to access the connection
    public function getConnection() {
        return $this->conn;
    }

    // ✅ User Registration with Automatic 10 Credits
    public function register($firstName, $lastName, $email, $password, $role) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $query = "INSERT INTO " . $this->table . " (first_name, last_name, email, password, role) 
                  VALUES (:first_name, :last_name, :email, :password, :role)";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":first_name", $firstName);
        $stmt->bindParam(":last_name", $lastName);
        $stmt->bindParam(":email", $email);
        $stmt->bindParam(":password", $hashedPassword);
        $stmt->bindParam(":role", $role);

        if ($stmt->execute()) {
            // Get the last inserted user_id
            $userId = $this->conn->lastInsertId();
            
            // Initialize credits for the new user
            return $this->initializeCredits($userId);
        }

        return false;
    }

    // ✅ Initialize Credits for New User
    private function initializeCredits($userId) {
        $query = "INSERT INTO proposal_credits (user_id, credits, last_reset) VALUES (:user_id, 10, CURDATE())";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $userId);
        
        return $stmt->execute();
    }

    // ✅ Check User by Email
    public function findUserByEmail($email) {
        $query = "SELECT * FROM " . $this->table . " WHERE email = :email";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $email);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // ✅ Verify User Email
    public function updateVerificationStatus($email) {
        $query = "UPDATE users SET is_verified = 1 WHERE email = :email";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $email);
        return $stmt->execute();
    }

    // ✅ Reset Daily Credits (Run this when a user logs in)
    public function resetDailyCredits($userId) {
        if (empty($userId)) {
            return false; // Prevent error when user ID is null
        }
    
        $query = "SELECT credits, last_reset FROM proposal_credits WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $userId);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
        $today = date("Y-m-d");
    
        if (!$result) {
            // If no record exists, create one with 10 credits
            $insertQuery = "INSERT INTO proposal_credits (user_id, credits, last_reset) VALUES (:user_id, 10, :last_reset)";
            $insertStmt = $this->conn->prepare($insertQuery);
            $insertStmt->bindParam(":user_id", $userId);
            $insertStmt->bindParam(":last_reset", $today);
            return $insertStmt->execute();
        } elseif ($result['last_reset'] !== $today) {
            // If last reset is not today, update credits to 10
            $updateQuery = "UPDATE proposal_credits SET credits = 10, last_reset = :last_reset WHERE user_id = :user_id";
            $updateStmt = $this->conn->prepare($updateQuery);
            $updateStmt->bindParam(":user_id", $userId);
            $updateStmt->bindParam(":last_reset", $today);
            return $updateStmt->execute();
        }
    
        return true; // Credits are already up to date
    }
    // ✅ Use Credit (Deduct 5 Credit)
    public function useCredit($userId) {
        $this->resetDailyCredits($userId); // Ensure credits are refreshed daily

        $query = "UPDATE proposal_credits SET credits = credits - 5  WHERE user_id = :user_id AND credits > 0";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $userId);
        $stmt->execute();

        return $stmt->rowCount() > 0; // Return true if at least one row was affected
    }

    // ✅ Get Current Credits
    public function getCredits($userId) {
        $this->resetDailyCredits($userId); // Ensure credits are up to date

        $query = "SELECT credits FROM proposal_credits WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $userId);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['credits'] : 0;
    }
    public function getUserById($userId) {
        $query = "SELECT * FROM " . $this->table . " WHERE id = :user_id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $userId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
}
