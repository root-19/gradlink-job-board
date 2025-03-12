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
    <title>GradLink - Job Seeker Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <nav class="bg-purple-900 p-4 flex flex-wrap justify-between items-center text-white">
    <a href="dashboard.php">
    <div class="text-xl font-bold cursor-pointer hover:text-blue-500">GRADLINK</div>
</a>
        <div class="space-x-2 flex flex-wrap justify-center">
        <button  id="find-work"  class="bg-white text-black p-2 rounded">Find Work</button>
            <script>
            const find = document.getElementById('find-work');
            find.addEventListener('click', function() {
                window.location.href ='dashboard.php';
            });
             </script>
            <button  id="post-talent"  class="bg-white text-black p-2 rounded">Post Talent</button>
            <script>
            const button = document.getElementById('post-talent');
            button.addEventListener('click', function() {
                window.location.href ='Talent.php';
            });
             </script>
                      <input type="text" id="jobSearch" placeholder="Find Keyword for Work"
    class="p-2 text-black rounded w-full md:w-auto" autocomplete="off">
<div id="suggestions" class="bg-white shadow-md absolute rounded w-full hidden"></div>

<script>
document.getElementById('jobSearch').addEventListener('input', function () {
    let searchQuery = this.value.trim();
    let suggestionsBox = document.getElementById('suggestions');

    if (searchQuery.length < 2) { 
        suggestionsBox.innerHTML = "";
        suggestionsBox.classList.add('hidden');
        return;
    }

    fetch(`search_jobs.php?query=${searchQuery}`)
        .then(response => response.json())
        .then(data => {
            console.log("Received Data:", data); // Debugging: Tignan ang response sa console
            suggestionsBox.innerHTML = "";

            if (data.length > 0 && !data.error) {
                suggestionsBox.classList.remove('hidden');
                data.forEach(job => {
                    let suggestion = document.createElement('div');
                    suggestion.textContent = job.job_title;
                    suggestion.classList.add('p-5','ml-20', 'text-black', 'hover:bg-gray-200', 'cursor-pointer');

                    suggestion.addEventListener('click', function () {
                        document.getElementById('jobSearch').value = this.textContent;
                        suggestionsBox.innerHTML = "";
                        suggestionsBox.classList.add('hidden');
                    });

                    suggestionsBox.appendChild(suggestion);
                });
            } else {
                console.log("No data found!");
                suggestionsBox.classList.add('hidden');
            }
        })
        .catch(error => console.error('Error fetching job titles:', error));
});

</script>
<style>
    #suggestions div {
    color: black !important;
}
</style>
        </div>
        <div class="space-x-2 flex flex-wrap justify-center mt-2 md:mt-0">
        <a href="notification.php" class="bg-blue-500 text-white px-4 py-2 rounded">Notification</a>
        <a href="jobseeker_chat.php" class="bg-blue-500 text-white px-4 py-2 rounded">Chat</a>
            
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
