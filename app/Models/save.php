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
$jobId = $_POST['job_id'];
$reaction = $_POST['reaction'];

// Check if user already reacted
$stmt = $conn->prepare("SELECT id, reaction_type FROM job_reactions WHERE user_id = ? AND job_id = ?");
$stmt->execute([$userId, $jobId]);
$existingReaction = $stmt->fetch(PDO::FETCH_ASSOC);

if ($existingReaction) {
    // Update existing reaction
    $stmt = $conn->prepare("UPDATE job_reactions SET reaction_type = ? WHERE id = ?");
    $stmt->execute([$reaction, $existingReaction['id']]);
} else {
    // Insert new reaction
    $stmt = $conn->prepare("INSERT INTO job_reactions (user_id, job_id, reaction_type) VALUES (?, ?, ?)");
    $stmt->execute([$userId, $jobId, $reaction]);
}

// Save job post if reaction is 'heart'
if ($reaction === 'heart') {
    $stmt = $conn->prepare("SELECT id FROM saved_jobs WHERE user_id = ? AND job_id = ?");
    $stmt->execute([$userId, $jobId]);
    $savedJob = $stmt->fetchColumn();

    if (!$savedJob) {
        $stmt = $conn->prepare("INSERT INTO saved_jobs (user_id, job_id) VALUES (?, ?)");
        $stmt->execute([$userId, $jobId]);
    }
} elseif ($reaction === 'boo') {
    // Remove from saved jobs if reaction is changed to 'boo'
    $stmt = $conn->prepare("DELETE FROM saved_jobs WHERE user_id = ? AND job_id = ?");
    $stmt->execute([$userId, $jobId]);
}

// Get updated heart and boo counts
$stmt = $conn->prepare("SELECT 
    SUM(CASE WHEN reaction_type = 'heart' THEN 1 ELSE 0 END) AS heart_count,
    SUM(CASE WHEN reaction_type = 'boo' THEN 1 ELSE 0 END) AS boo_count
    FROM job_reactions WHERE job_id = ?");
$stmt->execute([$jobId]);
$counts = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode(["success" => true, "heart_count" => $counts['heart_count'], "boo_count" => $counts['boo_count']]);
?>
