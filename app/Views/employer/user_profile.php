<?php
session_start();
require_once __DIR__ . '/../../Config/Database.php'; 

use App\Config\Database;

$database = new Database();
$conn = $database->connect(); 

// Check if user ID is provided in the URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("User profile not found.");
}

$user_id = $_GET['id'];

// Fetch user details
$query = "SELECT first_name, last_name, profile_image, bio FROM users WHERE id = :user_id";
$stmt = $conn->prepare($query);
$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("User not found.");
}

// Fetch talents posted by the user
$query = "SELECT * FROM talent WHERE user_id = :user_id ORDER BY post_date DESC";
$stmt = $conn->prepare($query);
$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$talents = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
    <nav class="bg-purple-900 p-4 flex flex-wrap justify-between items-center text-white">
    <a href="dashboard.php">
    <div class="text-xl font-bold cursor-pointer hover:text-blue-500">GRADLINK</div>
</a>
        <div class="space-x-2 flex flex-wrap justify-center">
            <select class="bg-white text-black p-2 rounded">
                <option>Post Work</option>
            </select>
            <button class="bg-white text-black p-2 rounded">Hire Talent</button>
            <input type="text" placeholder="Search for Talent" class="p-2 rounded w-full md:w-auto">
        </div>
        <div class="space-x-2 flex flex-wrap justify-center mt-2 md:mt-0">
            <button>🔔</button>
            <button>📧</button>
            <a href="logout.php" class="bg-red-500 p-2 rounded">Logout</a>
        </div>
    </nav>

    <div class="flex flex-col md:flex-row m-4">
        <div class="md:w-3/4 p-4">
            <

            <div class="bg-white p-4 rounded shadow-md mb-4">
           <!-- User Profile Section -->
           <div class="flex items-center">
                <img src="/uploads/<?= htmlspecialchars($user['profile_image'] ?? 'default-avatar.png'); ?>" 
                     alt="Profile Image" 
                     class="w-24 h-24 rounded-full object-cover">
                <div class="ml-4">
                    <h1 class="text-2xl font-bold"><?= htmlspecialchars($user['first_name'] . " " . $user['last_name']); ?></h1>
                    <p class="text-sm mt-1"><?= htmlspecialchars($user['bio'] ?? "No bio available."); ?></p>
                </div>
            </div>
        </div>

        <!-- User's Talents -->
        <div class="mt-6">
            <h2 class="text-xl font-semibold mb-4">Talents by <?= htmlspecialchars($user['first_name']); ?></h2>

            <?php if (count($talents) > 0): ?>
                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <?php foreach ($talents as $talent): ?>
                        <div class="bg-white p-4 rounded shadow-md">
                            <h2 class="text-xl font-semibold"><?= htmlspecialchars($talent['can_do']); ?></h2>
                            <p class="text-gray-600"><?= htmlspecialchars($talent['description']); ?></p>

                            <!-- Talent Image -->
                            <?php if (!empty($talent['image'])): ?>
                                <img src="/<?= htmlspecialchars($talent['image']); ?>" alt="Talent Image" class="w-full h-40 object-cover mt-2 rounded">
                            <?php endif; ?>

                            <div class="mt-2">
                                <span class="text-green-600 font-semibold">₱<?= number_format($talent['budget'], 2); ?></span>
                                <span class="text-gray-500 ml-2"><?= htmlspecialchars($talent['estimated_time']); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-gray-500">This user has not posted any talents yet.</p>
            <?php endif; ?>
        </div>
    </div>
        <div class="md:w-1/4 bg-white p-4 mt-20 rounded shadow-md">
    <div class="text-center">
        <!-- Profile Image -->

      <!-- Profile Image -->
<img src="/uploads/<?= htmlspecialchars($user['profile_image'] ?? 'default-avatar.png'); ?>" alt="Profile Image" 
class="w-24 h-24 mx-auto rounded-full object-cover">

<!-- Bio Section -->
<p class="text-sm mt-1"><?= htmlspecialchars($user['bio'] ?? "No bio available."); ?></p>


        <!-- Premium Section -->
        <button class="bg-yellow-500 w-full p-2 rounded mt-2">GO PREMIUM</button>
        <p class="text-sm mt-2">Plan: FREE</p>
        <button class="bg-gray-300 w-full p-2 rounded mt-2">Watch Ad Video to get more</button>
    </div>
</div>