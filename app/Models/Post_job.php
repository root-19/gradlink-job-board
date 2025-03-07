<?php
session_start();
require_once __DIR__ . '/../Config/Database.php'; 

use App\Config\Database;

$database = new Database();
$conn = $database->connect(); 

header("Content-Type: application/json");

// Ensure request is POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Invalid request method"]);
    exit;
}

// Check if the user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['first_name'])) {
    echo json_encode(["status" => "error", "message" => "User not authenticated"]);
    exit;
}

// Get form data from $_POST
$jobTitle = trim($_POST['job_title'] ?? '');
$jobDescription = trim($_POST['job_description'] ?? '');
$jobBudget = trim($_POST['budget'] ?? '');
$userId = $_SESSION['user_id'];
$firstName = $_SESSION['first_name'];

if ($jobTitle === "" || $jobDescription === "" || $jobBudget === "") {
    echo json_encode(["status" => "error", "message" => "All fields are required!"]);
    exit;
}

// Insert into database
$query = "INSERT INTO post_job (user_id, first_name, job_title, job_description, budget, post_date) 
          VALUES (:user_id, :first_name, :job_title, :job_description, :budget, NOW())";
$stmt = $conn->prepare($query);
$stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
$stmt->bindParam(':first_name', $firstName, PDO::PARAM_STR);
$stmt->bindParam(':job_title', $jobTitle, PDO::PARAM_STR);
$stmt->bindParam(':job_description', $jobDescription, PDO::PARAM_STR);
$stmt->bindParam(':budget', $jobBudget, PDO::PARAM_INT);

if ($stmt->execute()) {
    echo json_encode(["status" => "success", "message" => "Job posted successfully!"]);
} else {
    echo json_encode(["status" => "error", "message" => "Failed to post job"]);
}
?>
