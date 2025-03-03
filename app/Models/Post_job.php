<?php
session_start();

require_once __DIR__ . '/../Config/Database.php'; // Correct path

use App\Config\Database;

// Create a database connection
$database = new Database();
$conn = $database->connect(); 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(["status" => "error", "message" => "You must be logged in to post a job."]);
        exit();
    }

    $user_id = $_SESSION['user_id'];
    $first_name = $_SESSION['first_name'] ?? ''; // Handle missing session data
    $job_title = trim($_POST['job_title'] ?? '');
    $job_description = trim($_POST['job_description'] ?? '');
    $budget = trim($_POST['budget'] ?? '');

    // Validate input fields
    if (empty($job_title) || empty($job_description) || empty($budget)) {
        echo json_encode(["status" => "error", "message" => "All fields are required."]);
        exit();
    }

    try {
        // Check if the job already exists
        $checkStmt = $conn->prepare("SELECT id FROM post_job WHERE user_id = ? AND job_title = ?");
        $checkStmt->execute([$user_id, $job_title]);

        if ($checkStmt->rowCount() > 0) {
            echo json_encode(["status" => "error", "message" => "This job has already been posted."]);
            exit();
        }

        // Insert the job post into the database
        $stmt = $conn->prepare("INSERT INTO post_job (user_id, first_name, job_title, job_description, budget) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $first_name, $job_title, $job_description, $budget]);

        echo json_encode(["status" => "success", "message" => "Job post successfully submitted!"]);
    } catch (PDOException $e) {
        echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
    }
}
?>
