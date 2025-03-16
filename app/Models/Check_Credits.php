<?php
require_once __DIR__ . '/../Config/Database.php';

session_start();
$userId = $_SESSION['user_id']; // Adjust if you use a different session variable

$query = "SELECT credits FROM proposal_credits WHERE user_id = :user_id";
$stmt = $conn->prepare($query);
$stmt->bindParam(":user_id", $userId, PDO::PARAM_INT);
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);

if ($result) {
    echo json_encode(["credits" => (int)$result['credits']]);
} else {
    echo json_encode(["credits" => 0]);
}
?>
