<?php
session_start();

require_once __DIR__ . '/../../config/Database.php';

use App\Config\Database;
use App\Models\User;

require_once __DIR__ . '/../../Models/User.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employer') {
    header("Location: login.php");
    exit();
}

$database = new Database();
$conn = $database->connect();


// Fetch user details from session
$first_name = $_SESSION['first_name'] ?? 'Guest';
$last_name = $_SESSION['last_name'] ?? 'User';



// Fetch user details
$user_id = $_SESSION['user_id'] ?? 0; // Ensure $user_id is set
$stmt = $conn->prepare("SELECT profile_image, bio, first_name, last_name FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Set default profile image if none exists
$profile_image = !empty($user['profile_image']) ? $user['profile_image'] : 'default-avatar.png';
$bio = $user['bio'] ?? 'No bio available.';
$full_name = isset($user['first_name'], $user['last_name']) ? $user['first_name'] . ' ' . $user['last_name'] : 'Guest User';


// Fetch all talents
$query = "SELECT t.*, u.first_name, u.last_name FROM talent t 
          JOIN users u ON t.user_id = u.id 
          ORDER BY t.post_date DESC";
$stmt = $conn->prepare($query);
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
            <div class="flex flex-wrap space-x-2 mb-4">
                <button id="offer" class="bg-purple-700 text-white p-2 rounded">Offers</button>
                <!-- <button class="bg-purple-700 text-white p-2 rounded">Offers</button> -->
                <button  class="bg-gray-300 p-2 rounded">job seeker</button>
                <script> 
              const seeker = document.getElementById('offer');
              seeker.addEventListener('click', function() {
                window.location.href= 'dashboard.php';
              }) 
                </script>
                <button id="postJobBtn" class="bg-purple-700 text-white p-2 rounded">Post Job +</button>
            </div>

            <div id="jobPostModal" class="fixed inset-0 flex items-center justify-center bg-gray-900 bg-opacity-50 hidden">
                <div class="bg-white p-6 rounded shadow-lg w-96">
                    <h2 class="text-xl font-bold mb-2">Post a Job</h2>
                    <input type="text" id="jobTitle" placeholder="Job Title" class="p-2 border w-full mb-2">
                    <textarea id="jobDescription" placeholder="Job Description" class="p-2 border w-full mb-2"></textarea>
                    <input type="number" id="jobBudget" placeholder="Budget (PHP)" class="p-2 border w-full mb-2">
                    <button id="submitJob" class="bg-blue-500 text-white p-2 rounded w-full">Submit</button>
                    <button id="closeModal" class="bg-gray-400 text-white p-2 rounded w-full mt-2">Close</button>
                </div>
            </div>

            <div class="bg-white p-4 rounded shadow-md mb-4">
           <!-- DITO MO IDISPLAY -->
           <?php if (count($talents) > 0): ?>
                <?php foreach ($talents as $talent): ?>
                    <div class="border-b border-gray-300 py-4">
                        <div class="flex items-center space-x-4">
                            <!-- Talent Image -->
                            <img src="/<?= htmlspecialchars($talent['image']); ?>" 
                                 alt="Talent Image" class="w-16 h-16 rounded-full object-cover">
                            <div>
                            <a href="user_profile.php?id=<?= $talent['user_id']; ?>" 
                               class="ml-3 text-lg font-bold text-purple-700 hover:underline">
                                <?= htmlspecialchars($talent['first_name'] . ' ' . $talent['last_name']); ?>
                            </a>
                                <p class="text-gray-700"> <?= htmlspecialchars($talent['can_do']); ?> </p>
                            </div>
                        </div>
                        <p class="mt-2 text-sm"> <?= htmlspecialchars($talent['description']); ?> </p>
                        <p class="text-sm text-gray-500">Budget: <strong>₱<?= number_format($talent['budget']); ?></strong></p>
                        <p class="text-sm text-gray-500">Estimated Time: <?= htmlspecialchars($talent['estimated_time']); ?></p>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class='text-gray-500'>No talents available.</p>
            <?php endif; ?>
            </div>
        </div>

        <div class="md:w-1/4 bg-white p-4 mt-20 rounded shadow-md">
    <div class="text-center">
        <!-- Profile Image -->

        <img src="/uploads/<?= htmlspecialchars($profile_image); ?>" alt="Profile Image" 
     class="w-24 h-24 mx-auto rounded-full object-cover">

        
        <!-- User Info -->
        <h3 class="font-bold mt-2"><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h3>
        
        <!-- Bio Section -->
        <p class="text-sm mt-1"><?= htmlspecialchars($bio); ?></p>
        
     
        <!-- Premium Section -->
        <button class="bg-yellow-500 w-full p-2 rounded mt-2">GO PREMIUM</button>
        <p class="text-sm mt-2">Plan: FREE</p>
        <button class="bg-gray-300 w-full p-2 rounded mt-2">Watch Ad Video to get more</button>
    </div>
</div>


    </div>