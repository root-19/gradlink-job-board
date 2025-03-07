<?php
session_start();

include_once __DIR__ . '/../Config/Database.php';
// use App\Config\Database;
use App\Config\Database;

$database = new Database();
$conn = $database->connect();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "You must be logged in to apply."]);
    exit();
}

$userId = $_SESSION['user_id'];
$first_name = $_SESSION['first_name'] ?? '';
$job_id = $_POST['job_id'] ?? null;

if (!$job_id) {
    echo json_encode(["status" => "error", "message" => "Invalid job ID."]);
    exit();
}

// Fetch user credits
$stmt = $conn->prepare("SELECT credits FROM proposal_credits WHERE user_id = :user_id");
$stmt->bindParam(":user_id", $userId, PDO::PARAM_INT);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$proposal_credits = $user['credits'] ?? 0;

// Check if the user has enough credits
if ($proposal_credits < 5) {
    echo json_encode(["status" => "error", "message" => "Not enough proposal credits."]);
    exit();
}

// Check if the user has already applied for this job
$stmt = $conn->prepare("SELECT id FROM apply WHERE user_id = :user_id AND job_id = :job_id");
$stmt->bindParam(":user_id", $userId, PDO::PARAM_INT);
$stmt->bindParam(":job_id", $job_id, PDO::PARAM_INT);
$stmt->execute();

if ($stmt->rowCount() > 0) {
    echo json_encode(["status" => "error", "message" => "You have already applied for this job."]);
    exit();
}

try {
    // Deduct 5 credits
    $stmt = $conn->prepare("UPDATE proposal_credits SET credits = credits - 5 WHERE user_id = :user_id");
    $stmt->bindParam(":user_id", $userId, PDO::PARAM_INT);
    $stmt->execute();

    // Insert application record
    $stmt = $conn->prepare("INSERT INTO apply (user_id, first_name, job_id) VALUES (:user_id, :first_name, :job_id)");
    $stmt->bindParam(":user_id", $userId, PDO::PARAM_INT);
    $stmt->bindParam(":first_name", $first_name, PDO::PARAM_STR);
    $stmt->bindParam(":job_id", $job_id, PDO::PARAM_INT);
    $stmt->execute();

    echo json_encode(["status" => "success", "message" => "Successfully applied for the job!"]);
} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
}
?>
