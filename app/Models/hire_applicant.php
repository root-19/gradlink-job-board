<?php
session_start(); // Ensure session is started

require_once __DIR__ . '/../Config/Database.php'; 

use App\Config\Database;

$database = new Database();
$conn = $database->connect(); 

header('Content-Type: application/json');

// Decode JSON input
$data = json_decode(file_get_contents("php://input"), true);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "error" => "Unauthorized. Please log in."]);
    exit;
}

// Validate required fields
if (isset($data['user_id'], $data['job_id'])) {
    $user_id = intval($data['user_id']);
    $job_id = intval($data['job_id']);
    $employer_id = $_SESSION['user_id'];

    // Check if the applicant is already hired
    $checkQuery = "SELECT id FROM hired_applicants WHERE applicant_id = :user_id AND job_id = :job_id";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
    $checkStmt->bindParam(":job_id", $job_id, PDO::PARAM_INT);
    $checkStmt->execute();

    if ($checkStmt->rowCount() > 0) {
        echo json_encode(["success" => false, "error" => "This applicant has already been hired for this job."]);
        exit;
    }

    // Insert into hiring table
    $query = "INSERT INTO hired_applicants (employer_id, applicant_id, job_id) VALUES (:employer_id, :user_id, :job_id)";
    $stmt = $conn->prepare($query);
    
    if ($stmt === false) {
        echo json_encode(["success" => false, "error" => "Failed to prepare statement."]);
        exit;
    }

    $stmt->bindParam(":employer_id", $employer_id, PDO::PARAM_INT);
    $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
    $stmt->bindParam(":job_id", $job_id, PDO::PARAM_INT);

    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Applicant successfully hired!"]);
    } else {
        echo json_encode(["success" => false, "error" => "Failed to hire applicant: " . $stmt->errorInfo()[2]]);
    }
} else {
    echo json_encode(["success" => false, "error" => "Invalid data provided."]);
}
?>
