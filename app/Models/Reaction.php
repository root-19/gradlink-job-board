<?php
session_start();
require_once __DIR__ . '/../Config/Database.php';

use App\Config\Database;

$database = new Database();
$conn = $database->connect();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "You must be logged in to react."]);
    exit();
}

$userId = $_SESSION['user_id'];
$jobId = $_POST['job_id'] ?? null;
$reaction = $_POST['reaction'] ?? null;

if (!$jobId || !$reaction) {
    echo json_encode(["success" => false, "message" => "Invalid input."]);
    exit();
}

// Check if user already reacted
$stmt = $conn->prepare("SELECT id FROM job_reactions WHERE user_id = ? AND job_id = ?");
$stmt->execute([$userId, $jobId]);
$existingReaction = $stmt->fetchColumn();

if ($existingReaction) {
    // Update reaction
    $stmt = $conn->prepare("UPDATE job_reactions SET reaction_type = ? WHERE id = ?");
    $stmt->execute([$reaction, $existingReaction]);
} else {
    // Insert reaction
    $stmt = $conn->prepare("INSERT INTO job_reactions (user_id, job_id, reaction_type) VALUES (?, ?, ?)");
    $stmt->execute([$userId, $jobId, $reaction]);

    // If heart reaction, save post
    if ($reaction === 'heart') {
        $stmt = $conn->prepare("INSERT INTO saved_jobs (user_id, job_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE job_id = job_id");
        $stmt->execute([$userId, $jobId]);
    }
}

// Get updated heart and boo counts
$stmt = $conn->prepare("
    SELECT 
        SUM(CASE WHEN reaction_type = 'heart' THEN 1 ELSE 0 END) AS heart_count,
        SUM(CASE WHEN reaction_type = 'boo' THEN 1 ELSE 0 END) AS boo_count
    FROM job_reactions WHERE job_id = ?
");
$stmt->execute([$jobId]);
$counts = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode([
    "success" => true,
    "heart_count" => $counts['heart_count'],
    "boo_count" => $counts['boo_count']
]);
?>
