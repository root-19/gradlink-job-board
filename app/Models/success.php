<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use App\Config\Database;

header('Content-Type: application/json');

if (!isset($_GET['user_id']) || !isset($_GET['credits'])) {
    echo json_encode(["status" => "error", "message" => "Invalid payment data."]);
    exit;
}

$userId = (int) $_GET['user_id'];
$credits = (int) $_GET['credits'];

if ($credits <= 0 || $userId <= 0) {
    echo json_encode(["status" => "error", "message" => "Invalid user or credit data."]);
    exit;
}

// Initialize database connection
$database = new Database();
$conn = $database->connect();

try {
    // Insert the purchased credits into proposal_credits table
    $stmt = $conn->prepare("INSERT INTO proposal_credits (user_id, credits, purchase_date) VALUES (?, ?, NOW())");
    $stmt->execute([$userId, $credits]);

    echo json_encode(["status" => "success", "message" => "Credits added successfully."]);
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
}

?>
