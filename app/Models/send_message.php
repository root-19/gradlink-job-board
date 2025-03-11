<?php
session_start();
require_once __DIR__ . '/../Config/Database.php';
use App\Config\Database;
$database = new Database();
$conn = $database->connect();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'], $_POST['hire_id'])) {
    $sender_id = $_SESSION['user_id'];
    $hire_id = $_POST['hire_id'];
    $message = trim($_POST['message']);

    // Fetch receiver ID
    $query = "SELECT applicant_id, employer_id FROM hired_applicants WHERE id = :hire_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(":hire_id", $hire_id, PDO::PARAM_INT);
    $stmt->execute();
    $hire = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$hire) {
        die("Invalid conversation.");
    }

    // Determine receiver
    $receiver_id = ($sender_id == $hire['applicant_id']) ? $hire['employer_id'] : $hire['applicant_id'];

    // Insert message
    $insertQuery = "INSERT INTO messages (sender_id, receiver_id, hire_id, message) VALUES (:sender, :receiver, :hire_id, :message)";
    $insertStmt = $conn->prepare($insertQuery);
    $insertStmt->bindParam(":sender", $sender_id, PDO::PARAM_INT);
    $insertStmt->bindParam(":receiver", $receiver_id, PDO::PARAM_INT);
    $insertStmt->bindParam(":hire_id", $hire_id, PDO::PARAM_INT);
    $insertStmt->bindParam(":message", $message, PDO::PARAM_STR);

    if ($insertStmt->execute()) {
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit();
        
        
    }
}
?>
