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

  
    public function resetDailyCredits($userId) {
        if (empty($userId)) {
            return false;
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
    
        return true;
    }
   
    public function useCredit($userId) {
        $this->resetDailyCredits($userId); 

        $query = "UPDATE proposal_credits SET credits = credits - 5  WHERE user_id = :user_id AND credits > 0";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $userId);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    public function getCredits($userId) {
        $this->resetDailyCredits($userId); 

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


    public function resetEmployerCredits($userId) {
        if (empty($userId)) {
            return false;
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
    
        return true;
    }
    
   
public function useEmployerCredit($userId) {
    // Fetch current credits and last reset timestamp
    $query = "SELECT credits, last_reset FROM proposal_credits WHERE user_id = :user_id";
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(":user_id", $userId, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    // If no entry exists, create one with 10 credits and set the last_reset timestamp
    if (!$result) {
        $insertQuery = "INSERT INTO proposal_credits (user_id, credits, last_reset) VALUES (:user_id, 10, NOW())";
        $insertStmt = $this->conn->prepare($insertQuery);
        $insertStmt->bindParam(":user_id", $userId, PDO::PARAM_INT);
        $insertStmt->execute();
        return true; // Entry created
    }

    $currentCredits = (int) $result['credits'];
    $lastReset = $result['last_reset'];
    $oneDayAgo = date("Y-m-d H:i:s", strtotime("-1 day"));

    // 🔹 Prevent posting if the user has less than 5 credits
    if ($currentCredits < 5) {
        error_log("User {$userId} does not have enough credits to post.");
        return false;
    }

    // 🔹 Only reset to 10 credits if exactly 0 credits AND 24 hours have passed since last reset
    if ($currentCredits == 0 && strtotime($lastReset) <= strtotime($oneDayAgo)) {
        $resetQuery = "UPDATE proposal_credits SET credits = 10, last_reset = NOW() WHERE user_id = :user_id";
        $resetStmt = $this->conn->prepare($resetQuery);
        $resetStmt->bindParam(":user_id", $userId, PDO::PARAM_INT);
        $resetStmt->execute();
        return true; // Reset applied
    }

    // 🔹 Deduct 5 credits per post
    $updateQuery = "UPDATE proposal_credits SET credits = credits - 5 WHERE user_id = :user_id";
    $updateStmt = $this->conn->prepare($updateQuery);
    $updateStmt->bindParam(":user_id", $userId, PDO::PARAM_INT);
    $updateStmt->execute();

    return $updateStmt->rowCount() > 0;
}

    
}
