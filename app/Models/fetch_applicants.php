<?php
require_once __DIR__ . '/../Config/Database.php'; 

use App\Config\Database;

$database = new Database();
$conn = $database->connect(); 

header("Content-Type: application/json");

if (!isset($_GET['job_id'])) {
    echo json_encode([]);
    exit;
}

$jobId = $_GET['job_id'];

$query = "SELECT user_id, first_name FROM apply WHERE job_id = :job_id";
$stmt = $conn->prepare($query);
$stmt->bindParam(':job_id', $jobId, PDO::PARAM_INT);
$stmt->execute();
$applicants = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($applicants);
?>
