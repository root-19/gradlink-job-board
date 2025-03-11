<?php
session_start();
require_once __DIR__ . '/../../config/Database.php';

use App\Config\Database;
$database = new Database();
$conn = $database->connect();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$hire_id = $_GET['hire_id'] ?? null;

// Kunin lahat ng employer_id na nag-hire sa job seeker
$hiredQuery = "SELECT id, employer_id FROM hired_applicants WHERE applicant_id = :user_id";
$hiredStmt = $conn->prepare($hiredQuery);
$hiredStmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
$hiredStmt->execute();
$hiredEmployers = $hiredStmt->fetchAll(PDO::FETCH_ASSOC);

// Check if the job seeker is part of the conversation
$checkQuery = "SELECT * FROM hired_applicants WHERE id = :hire_id AND (applicant_id = :user_id OR employer_id = :user_id)";
$checkStmt = $conn->prepare($checkQuery);
$checkStmt->bindParam(":hire_id", $hire_id, PDO::PARAM_INT);
$checkStmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
$checkStmt->execute();
$hire = $checkStmt->fetch(PDO::FETCH_ASSOC);



// Fetch messages
$query = "SELECT * FROM messages WHERE hire_id = :hire_id ORDER BY timestamp ASC";
$stmt = $conn->prepare($query);
$stmt->bindParam(":hire_id", $hire_id, PDO::PARAM_INT);
$stmt->execute();
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-5">
    <div class="max-w-4xl mx-auto flex">
        <!-- Sidebar for Hired Employers -->
        <div class="w-1/4 bg-white shadow-lg p-4 rounded h-screen overflow-y-auto">
            <h2 class="text-lg font-bold mb-4">Hired Employers</h2>
            <ul>
                <?php foreach ($hiredEmployers as $employer): ?>
                    <li class="mb-2">
                        <a href="jobseeker_chat.php?hire_id=<?= $employer['id'] ?>" class="block p-2 bg-blue-500 text-white rounded hover:bg-blue-700">
                            Employer #<?= $employer['employer_id'] ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <!-- Chat Section -->
        <div class="w-3/4 bg-white shadow-lg p-6 rounded ml-4">
            <h2 class="text-xl font-bold mb-4">Chat</h2>

            <div class="overflow-y-auto h-80 border p-3">
                <?php foreach ($messages as $message): ?>
                    <div class="mb-2 <?= $message['sender_id'] == $user_id ? 'text-right' : 'text-left' ?>">
                        <span class="text-sm text-gray-500"><?= date('H:i', strtotime($message['timestamp'])) ?></span>
                        <p class="p-2 inline-block rounded-lg <?= $message['sender_id'] == $user_id ? 'bg-blue-500 text-white' : 'bg-gray-300' ?>">
                            <?= htmlspecialchars($message['message']) ?>
                        </p>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Message Form -->
            <form action="../../Models/send_message.php" method="POST" class="mt-4 flex">
                <input type="hidden" name="hire_id" value="<?= $hire_id ?>">
                <input type="text" name="message" placeholder="Type your message..." class="w-full p-2 border rounded">
                <button type="submit" class="ml-2 bg-blue-500 text-white px-4 py-2 rounded">Send</button>
            </form>
        </div>
    </div>
</body>
</html>
