<?php
session_start();

require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../../Models/User.php';

use App\Config\Database;
use App\Models\User;

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'job_seeker') {
    header("Location: login.php");
    exit();
}

$database = new Database();
$conn = $database->connect();

$user_id = $_SESSION['user_id'];

// Fetch hired applicants for the logged-in user
$query = "SELECT h.id, h.job_id, j.job_title, 
                 u1.first_name AS applicant_name, 
                 u2.first_name AS employer_name, 
                 h.applicant_id, h.employer_id 
          FROM hired_applicants h
          JOIN post_job j ON h.job_id = j.id
          JOIN users u1 ON h.applicant_id = u1.id
          JOIN users u2 ON h.employer_id = u2.id
          WHERE h.applicant_id = :user_id
          ORDER BY h.id DESC";

$stmt = $conn->prepare($query);
$stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
$stmt->execute();
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle accept button click
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hire_id'], $_POST['action'])) {
    $hire_id = $_POST['hire_id'];
    $new_status = $_POST['action'] === 'accept' ? 'Accepted' : 'Rejected';

    $updateQuery = "UPDATE hired_applicants SET status = :status WHERE id = :hire_id";
    $updateStmt = $conn->prepare($updateQuery);
    $updateStmt->bindParam(":status", $new_status, PDO::PARAM_STR);
    $updateStmt->bindParam(":hire_id", $hire_id, PDO::PARAM_INT);

    if ($updateStmt->execute()) {
        if ($new_status === 'Accepted') {
            // Change user role to 'employee' when they accept the job
            $updateRoleQuery = "UPDATE users SET role = 'employee' WHERE id = :user_id";
            $updateRoleStmt = $conn->prepare($updateRoleQuery);
            $updateRoleStmt->bindParam(":user_id", $_SESSION['user_id'], PDO::PARAM_INT);
            $updateRoleStmt->execute();
        }

        header("Location: notification.php"); // Refresh the page
        exit();
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GradLink - Job Seeker Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <nav class="bg-purple-900 p-4 flex flex-wrap justify-between items-center text-white">
        <div class="text-xl font-bold">GRADLINK</div>
        <div class="space-x-2 flex flex-wrap justify-center">
            <select class="bg-white text-black p-2 rounded">
                <option>Find Work</option>
            </select>
            <button  id="post-talent"  class="bg-white text-black p-2 rounded">Post Talent</button>
            <script>
            const button = document.getElementById('post-talent');
            button.addEventListener('click', function() {
                window.location.href ='Talent.php';
            });
             </script>
            <input type="text" placeholder="Find Keyword for Work" class="p-2 rounded w-full md:w-auto">
        </div>
        <div class="space-x-2 flex flex-wrap justify-center mt-2 md:mt-0">
        <a href="notification.php" class="bg-blue-500 text-white px-4 py-2 rounded">Notification</a>
            <button>📧</button>
            <a href="logout.php" class="bg-red-500 p-2 rounded">Logout</a>
        </div>
    </nav>

    <div class="flex flex-col md:flex-row m-4">
        <div class="md:w-3/4 p-4">
            <div class="flex flex-wrap space-x-2 mb-4">
                <button class="bg-purple-700 text-white p-2 rounded">For You</button>
                <button class="bg-gray-300 p-2 rounded">Most Recent</button>
                <button id="save" class="bg-gray-300 p-2 rounded">Saved</button>
                <script>
                    const save = document.getElementById('save');
                    save.addEventListener('click', function(){
                        window.location.href = 'save_jobs.php';
                    });
                    </script>
                <!-- <button class="bg-gray-300 p-2 rounded">Filter Based on Talent</button> -->
            </div>
    <div class="max-w-2xl mx-auto bg-white shadow-lg p-6 rounded">
        <h2 class="text-xl font-bold mb-4">Hired Applicant Notifications</h2>
        
        <?php if (count($notifications) > 0): ?>
            <ul>
                <?php foreach ($notifications as $notification): ?>
                    <li class="p-3 border-b border-gray-300">
                        <strong>Applicant:</strong> <?= htmlspecialchars($notification['applicant_name']) ?><br>
                        <strong>Job Title:</strong> <?= htmlspecialchars($notification['job_title']) ?><br>
                        <strong>Employer:</strong> <?= htmlspecialchars($notification['employer_name']) ?><br>
                        <span class="text-gray-500 text-sm">Hired for job ID: <?= $notification['job_id'] ?></span>
                        
                        <form method="POST" class="mt-2">
                            <input type="hidden" name="accept_id" value="<?= $notification['id'] ?>">
                            <button type="submit" class="bg-green-500 text-white px-3 py-1 rounded">
                                Accept Job Offer
                            </button>
                        </form>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p class="text-gray-600">No notifications available.</p>
        <?php endif; ?>
    </div>
</body>
</html>
