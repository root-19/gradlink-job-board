<?php
require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../../Models/User.php';

use App\Models\User;
use App\Config\Database;


if (!isset($_GET['user_id']) || empty($_GET['user_id'])) {
    die("Invalid user ID.");
}

$userId = $_GET['user_id'];
$userModel = new User();
$user = $userModel->getUserById($userId);

if (!$user) {
    die("User not found.");
}

// Database connection
$database = new Database();
$conn = $database->connect();

// Fetch job posts by this user
$query = "SELECT * FROM post_job WHERE user_id = :user_id ORDER BY post_date DESC";
$stmt = $conn->prepare($query);
$stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
$stmt->execute();
$jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GradLink - Job Seeker Profile</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">

    <!-- Header -->
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
            <button id="post-talent" class="bg-white text-black p-2 rounded">Post Talent</button>
            <script>
                document.getElementById('post-talent').addEventListener('click', function() {
                    window.location.href = 'Talent.php';
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

        <div class="space-x-2 flex flex-wrap justify-center mt-2 md:mt-0">
        <a href="notification.php" class="bg-blue-500 text-white px-4 py-2 rounded">Notification</a>
        <a href="jobseeker_chat.php" class="bg-blue-500 text-white px-4 py-2 rounded">Chat</a>
            
            <a href="logout.php" class="bg-red-500 p-2 rounded">Logout</a>
        </div>
    </nav>

    <!-- Profile Section -->
    <div class="max-w-4xl mx-auto bg-white p-6 mt-6 shadow-md rounded">
        <div class="flex flex-col md:flex-row items-center">
            <!-- Profile Image -->
            <img src="/uploads/<?= htmlspecialchars($user['profile_image'] ?? 'default-avatar.png'); ?>" 
                alt="Profile Image" class="w-32 h-32 rounded-full object-cover shadow-md">

            <div class="md:ml-6 mt-4 md:mt-0 text-center md:text-left">
                <h2 class="text-2xl font-bold"><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h2>
                <p class="text-gray-600 mt-2"><?= nl2br(htmlspecialchars($user['bio'] ?? 'No bio available.')); ?></p>
            </div>
        </div>
    </div>

    <!-- Job Posts Section -->
    <div class="max-w-4xl mx-auto mt-6">
        <h2 class="text-xl font-bold mb-4">Job Posts by <?= htmlspecialchars($user['first_name']); ?></h2>

        <?php if (empty($jobs)): ?>
            <p class="text-gray-500">This user has not posted any jobs yet.</p>
        <?php else: ?>
            <?php foreach ($jobs as $job): ?>
                <div class="bg-white p-4 rounded shadow-md mb-4">
                    <h3 class="font-bold text-lg">LOOKING FOR: <?= htmlspecialchars($job['job_title']) ?></h3>
                    <p class="text-sm text-gray-500">Budget: $<?= htmlspecialchars($job['budget']) ?> | PHP</p>
                    <p class="text-gray-700"><?= nl2br(htmlspecialchars($job['job_description'])) ?></p>
                    <div class="text-sm text-gray-500">Posted at: <?= htmlspecialchars($job['post_date']) ?></div>

                    <!-- <div class="mt-2">
                        <a href="job_details.php?job_id=<?= $job['id'] ?>" 
                            class="text-blue-500 hover:underline">View Details</a>
                    </div> -->
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</body>
</html>
