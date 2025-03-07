<?php
session_start();
require_once __DIR__ . '/../Config/Database.php';

use App\Config\Database;

$database = new Database();
$conn = $database->connect();

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "You must be logged in."]);
    exit();
}

$userId = $_SESSION['user_id'];
$jobId = $_POST['job_id'] ?? null;

if (!$jobId) {
    echo json_encode(["success" => false, "message" => "Invalid job ID."]);
    exit();
}

// Remove job from saved jobs
$stmt = $conn->prepare("DELETE FROM saved_jobs WHERE user_id = ? AND job_id = ?");
$stmt->execute([$userId, $jobId]);

echo json_encode(["success" => true]);
