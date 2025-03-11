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

// Fetch hired applicants without redundancy
$hiredQuery = "
    SELECT DISTINCT h.id, u.first_name, u.last_name 
    FROM hired_applicants h
    JOIN users u ON h.applicant_id = u.id
    WHERE h.employer_id = :user_id
";
$hiredStmt = $conn->prepare($hiredQuery);
$hiredStmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
$hiredStmt->execute();
$hiredApplicants = $hiredStmt->fetchAll(PDO::FETCH_ASSOC);

// Check if user is part of the conversation
$checkQuery = "SELECT * FROM hired_applicants WHERE id = :hire_id AND employer_id = :user_id";
$checkStmt = $conn->prepare($checkQuery);
$checkStmt->bindParam(":hire_id", $hire_id, PDO::PARAM_INT);
$checkStmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
$checkStmt->execute();
$hire = $checkStmt->fetch(PDO::FETCH_ASSOC);

// Fetch messages if hire_id exists
$messages = [];
if ($hire_id) {
    $query = "SELECT * FROM messages WHERE hire_id = :hire_id ORDER BY timestamp ASC";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(":hire_id", $hire_id, PDO::PARAM_INT);
    $stmt->execute();
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GradLink - Employer Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <!-- Navbar -->
    <nav class="bg-purple-900 p-4 flex justify-between items-center text-white">
        <a href="dashboard.php">
            <div class="text-xl font-bold cursor-pointer hover:text-blue-500">GRADLINK</div>
        </a>
        <div class="flex space-x-2">
            <select class="bg-white text-black p-2 rounded">
                <option>Post Work</option>
            </select>
            <button class="bg-white text-black p-2 rounded">Hire Talent</button>
            <div class="relative">
                <input type="text" id="searchTalent" placeholder="Search for Talent" class="p-2 rounded w-60">
                <div id="suggestions" class="bg-white shadow-md rounded mt-1 absolute w-full hidden"></div>
            </div>
        </div>
        <div class="flex space-x-2">
            <button class="text-lg">🔔</button>
            <a href="chat.php" class="bg-blue-500 text-white px-4 py-2 rounded">Chat</a>
            <a href="logout.php" class="bg-red-500 p-2 rounded">Logout</a>
        </div>
    </nav>

    <!-- Main Layout -->
    <div class="flex h-screen p-4">
        <!-- Sidebar for Hired Applicants -->
        <div class="w-1/4 bg-white shadow-lg p-4 rounded-lg h-full overflow-y-auto">
            <h2 class="text-lg font-bold mb-4">Hired Applicants</h2>
            <ul>
                <?php foreach ($hiredApplicants as $applicant): ?>
                    <li class="mb-2">
                        <a href="chat.php?hire_id=<?= $applicant['id'] ?>" 
                           class="block p-2 bg-blue-500 text-white rounded hover:bg-blue-700">
                            <?= htmlspecialchars($applicant['first_name'] . ' ' . $applicant['last_name']) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <!-- Chat Section -->
        <div class="w-3/4 bg-white shadow-lg p-6 rounded-lg ml-4 flex flex-col">
            <h2 class="text-xl font-bold mb-4">Chat</h2>

            <div class="flex-1 overflow-y-auto h-96 border p-3 rounded-lg bg-gray-50">
                <?php if ($hire_id): ?>
                    <?php foreach ($messages as $message): ?>
                        <div class="mb-2 flex <?= $message['sender_id'] == $user_id ? 'justify-end' : 'justify-start' ?>">
                            <div class="max-w-xs p-3 rounded-lg <?= $message['sender_id'] == $user_id ? 'bg-blue-500 text-white' : 'bg-gray-300' ?>">
                                <span class="block text-sm text-gray-500"> <?= date('H:i', strtotime($message['timestamp'])) ?> </span>
                                <p><?= htmlspecialchars($message['message']) ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-center text-gray-500">Select a hired applicant to start chatting.</p>
                <?php endif; ?>
            </div>

            <!-- Message Form -->
            <?php if ($hire_id): ?>
                <form action="../../Models/send_message.php" method="POST" class="mt-4 flex">
                    <input type="hidden" name="hire_id" value="<?= $hire_id ?>">
                    <input type="text" name="message" placeholder="Type your message..." class="w-full p-2 border rounded-lg">
                    <button type="submit" class="ml-2 bg-blue-500 text-white px-4 py-2 rounded-lg">Send</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
