<?php
namespace App\Models;
include_once __DIR__ . '/../Config/Database.php';
use App\Config\Database;
use PDO;

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

    public function register($firstName, $lastName, $email, $password, $role) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $query = "INSERT INTO " . $this->table . " (first_name, last_name, email, password, role) VALUES (:first_name, :last_name, :email, :password, :role)";
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":first_name", $firstName);
        $stmt->bindParam(":last_name", $lastName);
        $stmt->bindParam(":email", $email);
        $stmt->bindParam(":password", $hashedPassword);
        $stmt->bindParam(":role", $role);

        return $stmt->execute();
    }

    public function findUserByEmail($email) {
        $query = "SELECT * FROM " . $this->table . " WHERE email = :email";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $email);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateVerificationStatus($email) {
        $query = "UPDATE users SET is_verified = 1 WHERE email = :email";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $email);
        return $stmt->execute();
    }
}
