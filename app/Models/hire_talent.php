<?php
session_start();
require_once __DIR__ . '/../Config/Database.php'; 

use App\Config\Database;
$database = new Database();
$conn = $database->connect();

// Ensure employer is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employer') {
    header("Location: login.php");
    exit();
}

// Get employer ID and talent ID
$employer_id = $_SESSION['user_id'];
$talent_id = $_POST['talent_id'] ?? 0;

if ($talent_id == 0) {
    header("Location: seeker.php?error=Invalid Talent ID");
    exit();
}
// Check if the application exists
$checkQuery = "SELECT id FROM apply WHERE user_id = ? AND status = 'Pending' LIMIT 1";
$checkStmt = $conn->prepare($checkQuery);
$checkStmt->execute([$talent_id]);
$application = $checkStmt->fetch(PDO::FETCH_ASSOC);

if (!$application) {
    echo "<script>alert('No pending application found'); window.history.back();</script>";
    exit();
}

// Now update the application status
$updateQuery = "UPDATE apply SET status = 'Hired' WHERE user_id = ? AND status = 'Pending'";
$updateStmt = $conn->prepare($updateQuery);
$updateStmt->execute([$talent_id]);

if ($updateStmt->rowCount() > 0) {
    echo "<script>alert('Talent Hired Successfully'); window.location.href = '../views/employer/seeker.php';</script>";
} else {
    echo "<script>alert('No pending application found'); window.history.back();</script>";
}
exit();

?>
