<?php
session_start();

require_once __DIR__ . '/../../config/Database.php';

use App\Config\Database;


if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'job_seeker') {
    header("Location: login.php");
    exit();
}

$database = new Database();
$conn = $database->connect();

// Fetch job posts
try {
    $stmt = $conn->prepare("SELECT id, first_name, job_title, job_description, budget, post_date FROM post_job ORDER BY post_date DESC");
    $stmt->execute();
    $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching jobs: " . $e->getMessage());
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
            <button class="bg-white text-black p-2 rounded">Post Talent</button>
            <input type="text" placeholder="Find Keyword for Work" class="p-2 rounded w-full md:w-auto">
        </div>
        <div class="space-x-2 flex flex-wrap justify-center mt-2 md:mt-0">
            <button>🔔</button>
            <button>📧</button>
            <a href="logout.php" class="bg-red-500 p-2 rounded">Logout</a>
        </div>
    </nav>

    <div class="flex flex-col md:flex-row m-4">
        <div class="md:w-3/4 p-4">
            <div class="flex flex-wrap space-x-2 mb-4">
                <button class="bg-purple-700 text-white p-2 rounded">For You</button>
                <button class="bg-gray-300 p-2 rounded">Most Recent</button>
                <button class="bg-gray-300 p-2 rounded">Saved</button>
                <button class="bg-gray-300 p-2 rounded">Filter Based on Talent</button>
            </div>
            <div class="bg-white p-4 rounded shadow-md mb-4">
            <?php if (empty($jobs)): ?>
            <p class="text-gray-500">No job posts available.</p>
        <?php else: ?>
            <?php foreach ($jobs as $job): ?>
                <div class="bg-white p-4 rounded shadow-md mb-4">
                    <h2 class="font-bold">LOOKING FOR: <?= htmlspecialchars($job['job_title']) ?></h2>
                    <p class="text-sm text-gray-500">Budget: $<?= htmlspecialchars($job['budget']) ?> | PHP</p>
                    <p class="text-gray-700">Description: <?= nl2br(htmlspecialchars($job['job_description'])) ?></p>
                    <div class="text-sm text-gray-500">Posted at: <?= htmlspecialchars($job['post_date']) ?></div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
            </div>
        </div>

        <div class="md:w-1/4 bg-white p-4 rounded shadow-md mt-4 md:mt-0">
            <div class="text-center">
                <div class="bg-gray-300 w-24 h-24 mx-auto rounded-full"></div>
                <h3 class="font-bold mt-2">NAME SURNAME</h3>
                <p class="text-sm">Educational Attainment</p>
                <button class="bg-yellow-500 w-full p-2 rounded mt-2">GO PREMIUM</button>
            </div>
        </div>
    </div>
</body>
</html>
