<?php
session_start();
require_once __DIR__ . '/../Config/Database.php'; 

use App\Config\Database;
$database = new Database();
$conn = $database->connect();

try {
    $stmt = $conn->prepare("SELECT id, first_name, job_title, job_description, budget FROM post_job ORDER BY created_at DESC");
    $stmt->execute();
    $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Debugging: Check if the query fetched any results
    if (empty($jobs)) {
        echo json_encode(["status" => "error", "message" => "No job posts found."]);
    } else {
        echo json_encode($jobs);
    }
} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Failed to fetch jobs: " . $e->getMessage()]);
}