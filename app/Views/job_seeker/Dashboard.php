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

$userId = $_SESSION['user_id'];
$userModel = new User();

// Fetch job posts with reactions
try {
    $stmt = $conn->prepare("SELECT p.id, p.user_id, p.first_name, p.job_title, p.job_description, p.budget, p.post_date, 
                            COALESCE(SUM(CASE WHEN r.reaction_type = 'heart' THEN 1 ELSE 0 END), 0) AS heart_count,
                            COALESCE(SUM(CASE WHEN r.reaction_type = 'boo' THEN 1 ELSE 0 END), 0) AS boo_count
                            FROM post_job p
                            LEFT JOIN job_reactions r ON p.id = r.job_id
                            GROUP BY p.id, p.user_id
                            ORDER BY p.post_date DESC");
    $stmt->execute();
    $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching jobs: " . $e->getMessage());
}

// Fetch user proposal credits
try {
    $stmt = $conn->prepare("SELECT credits FROM proposal_credits WHERE user_id = :user_id");
    $stmt->bindParam(":user_id", $userId, PDO::PARAM_INT);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $proposal_credits = $user['credits'] ?? 10; 
} catch (PDOException $e) {
    die("Error fetching proposal credits: " . $e->getMessage());
}

// Fetch user profile details
try {
    $stmt = $conn->prepare("SELECT profile_image, bio, first_name, last_name FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    $first_name = $user['first_name'] ?? 'Guest';
    $last_name = $user['last_name'] ?? 'User';
    $profile_image = !empty($user['profile_image']) ? $user['profile_image'] : 'default-avatar.png';
    $bio = $user['bio'] ?? 'No bio available.';
} catch (PDOException $e) {
    die("Error fetching user details: " . $e->getMessage());
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
            <div class="bg-white p-4 rounded shadow-md mb-4">
    <?php if (empty($jobs)): ?>
        <p class="text-gray-500">No job posts available.</p>
    <?php else: ?>
        <?php foreach ($jobs as $job): ?>
            <div class="bg-white p-4 rounded shadow-md mb-4">
                <div class="flex space-x-4 justify-end">
                    <button id="heart-button-<?= $job['id'] ?>" onclick="reactToJob(<?= $job['id'] ?>, 'heart')" class="reaction-button text-red-500">
                        ❤️ <span id="heart-count-<?= $job['id'] ?>"><?= $job['heart_count'] ?></span>
                    </button>
                    <button id="boo-button-<?= $job['id'] ?>" onclick="reactToJob(<?= $job['id'] ?>, 'boo')" class="reaction-button text-gray-500">
                        👎 <span id="boo-count-<?= $job['id'] ?>"><?= $job['boo_count'] ?></span>
                    </button>
                </div>

                <div class="text-sm text-gray-500">
    Posted by: 
    <a href="user_profile.php?user_id=<?= htmlspecialchars($job['user_id']); ?>" class="text-blue-500 hover:underline">
        <?= htmlspecialchars($job['first_name']); ?>
    </a>
</div>

                <h2 class="font-bold">LOOKING FOR: <?= htmlspecialchars($job['job_title']) ?></h2>
                <p class="text-sm text-gray-500">Budget: $<?= htmlspecialchars($job['budget']) ?> | PHP</p>
                <p class="text-gray-700">Description: <?= nl2br(htmlspecialchars($job['job_description'])) ?></p>
                <div class="text-sm text-gray-500">Posted at: <?= htmlspecialchars($job['post_date']) ?></div>

                <div class="flex space-x-4 mt-2">
                    <button 
                        class="bg-purple-900 text-white px-4 py-2 rounded <?= ($proposal_credits <= 0) ? 'opacity-50 cursor-not-allowed' : '' ?>" 
                        onclick="applyForJob(<?= $job['id'] ?>)" 
                        <?= ($proposal_credits <= 0) ? 'disabled' : '' ?>
                    >
                        Apply
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
function applyForJob(jobId) {
    Swal.fire({
        title: "Are you sure?",
        text: "Applying will deduct 5 credits from your account.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: "Yes, apply!",
    }).then((result) => {
        if (result.isConfirmed) {
            fetch("../../Models/Apply.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: "job_id=" + jobId,
            })
            .then(response => response.json())
            .then(data => {
                Swal.fire({
                    title: data.status === "success" ? "Applied!" : "Error!",
                    text: data.message,
                    icon: data.status === "success" ? "success" : "error",
                }).then(() => {
                    if (data.status === "success") {
                        location.reload(); // Refresh page to update credits
                    }
                });
            });
        }
    });
}
</script>
        </div>

        <div class="md:w-1/4 bg-white p-4 mb-20 rounded shadow-md mt-4 md:mt-0">
       <div class="text-center">
   <img src="/uploads/<?= htmlspecialchars($profile_image); ?>" 
     class="w-24 h-24 mx-auto rounded-full object-cover">
     <?= htmlspecialchars($job['first_name']); ?>
        <h3 class="font-bold mt-2"><?= htmlspecialchars($first_name) . ' ' . htmlspecialchars($last_name); ?></h3>
        <p class="texct-sm mt-1"><?= htmlspecialchars($bio);?></p>
        <button onclick="openModal()" class="bg-purple-900 text-white w-full p-2 rounded mt-2">edit profile</button>
        <div class="mt-2 p-2 bg-gray-100 rounded">
    <p class="text-sm font-semibold">Proposal Credits:</p>
    <p class="text-lg font-bold text-green-600"><?= htmlspecialchars($proposal_credits); ?></p>
            </div>
      
            <!-- <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> -->
            <button class="go-premium-btn bg-yellow-500 w-full p-2 rounded mt-2">GO PREMIUM</button>

        
           <!-- Modal for Editing Profile -->
<div id="editProfileModal" class="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center hidden">
    <div class="bg-white p-6 rounded shadow-lg w-1/3">
        <!-- <h2 class="text-lg font-bold mb-4">Edit Profile</h2> -->
        <form action="../../Models/job_seeker_profile.php" method="POST" enctype="multipart/form-data">
        <h2 class="text-lg font-bold mb-4">Edit Profile</h2>
            <!-- Profile Image Upload -->
            <label class="block text-sm font-medium">Profile Image:</label>
            <input type="file" name="profile_image" class="border p-2 w-full rounded mb-3">

            <!-- Hidden Input for Existing Image -->
            <input type="hidden" name="existing_profile_image" value="<?= htmlspecialchars($profile_image); ?>">

            <!-- Bio Edit -->
            <label class="block text-sm font-medium">Bio:</label>
            <textarea name="bio" class="border p-2 w-full rounded mb-3"><?= htmlspecialchars($bio); ?></textarea>

            <!-- Save Changes Button -->
            <button type="submit" class="bg-blue-500 text-white p-2 w-full rounded">Save Changes</button>
        </form>

        <!-- Close Modal -->
        <button onclick="closeModal()" class="bg-red-500 text-white p-2 w-full rounded mt-2">Cancel</button>
    </div>
</div>

<script>
    function openModal() {
        document.getElementById('editProfileModal').classList.remove('hidden');
    }

    function closeModal() {
        document.getElementById('editProfileModal').classList.add('hidden');
    }
</script>    </div>
</div>

    </div>
    <style>
    .reaction-button {
        transition: transform 0.2s ease-in-out;
    }
    .reaction-button:hover {
        transform: scale(1.1);
    }
</style>
<script>
 document.addEventListener("DOMContentLoaded", function () {
    document.querySelector(".go-premium-btn").addEventListener("click", function () {
        Swal.fire({
            title: "Choose Credits to Buy",
            input: "select",
            inputOptions: {
                50: "50 Credits",
                100: "100 Credits",
                150: "150 Credits",
                200: "200 Credits",
                250: "250 Credits",
                300: "300 Credits",
                500: "500 Credits"
            },
            inputPlaceholder: "Select an option",
            showCancelButton: true,
            confirmButtonText: "Buy Now",
            cancelButtonText: "Cancel",
            icon: "info"
        }).then((result) => {
            if (result.isConfirmed && result.value) {
                let selectedCredits = result.value;

                // Send request to process purchase
                fetch("../../Models/BuyCredits.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: "credits=" + selectedCredits
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === "success" && data.payment_url) {
                        window.location.href = data.payment_url; // Redirect to PayMongo
                    } else {
                        Swal.fire({
                            title: "Error!",
                            text: data.message,
                            icon: "error",
                        });
                    }
                });
            }
        });
    });
});

</script>
<script>
    function reactToJob(jobId, reaction) {
    const button = document.getElementById(`${reaction}-button-${jobId}`);
    button.classList.add("scale-125"); // Temporary scale effect on click
    setTimeout(() => button.classList.remove("scale-125"), 200);

    fetch('../../Models/Reaction.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `job_id=${jobId}&reaction=${reaction}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById(`heart-count-${jobId}`).innerText = data.heart_count;
            document.getElementById(`boo-count-${jobId}`).innerText = data.boo_count;
        } else {
            console.error("Error updating reaction:", data.message);
        }
    })
    .catch(error => console.error("Error:", error));
}
    </script>

    <script>
        function removeSavedJob(jobId) {
            fetch('../../Models/removed_save.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `job_id=${jobId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById(`job-${jobId}`).remove();
                } else {
                    alert(data.message);
                }
            })
            .catch(error => console.error('Error:', error));
        }
    </script>
</body>
</html>
