<?php
session_start();
require_once __DIR__ . '/../../Config/Database.php';

use App\Config\Database;

$database = new Database();
$conn = $database->connect();

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "You must be logged in to view saved jobs.";
    exit();
}

$userId = $_SESSION['user_id'];

// Fetch saved jobs
$stmt = $conn->prepare("
    SELECT j.id, j.job_title, j.budget, j.job_description, j.post_date, u.first_name
    FROM saved_jobs sj
    JOIN post_job j ON sj.job_id = j.id
    JOIN users u ON j.user_id = u.id
    WHERE sj.user_id = ?
");
$stmt->execute([$userId]);
$savedJobs = $stmt->fetchAll(PDO::FETCH_ASSOC);


use App\Models\User;

require_once __DIR__ . '/../../Models/User.php';

$userModel = new User();
$userId = $_SESSION['user_id'];


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

try {
    $stmt = $conn->prepare("SELECT p.id, p.first_name, p.job_title, p.job_description, p.budget, p.post_date, 
                            COALESCE(SUM(CASE WHEN r.reaction_type = 'heart' THEN 1 ELSE 0 END), 0) AS heart_count,
                            COALESCE(SUM(CASE WHEN r.reaction_type = 'boo' THEN 1 ELSE 0 END), 0) AS boo_count
                            FROM post_job p
                            LEFT JOIN job_reactions r ON p.id = r.job_id
                            GROUP BY p.id
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

    // Assign the fetched value or set default (e.g., 10 credits)
    $proposal_credits = $user['credits'] ?? 10; 
} catch (PDOException $e) {
    die("Error fetching proposal credits: " . $e->getMessage());
}


// Fetch user details from session
$first_name = $_SESSION['first_name'] ?? 'Guest';
$last_name = $_SESSION['last_name'] ?? 'User';


$user_id = $_SESSION['user_id'] ?? 0;
$stmt = $conn->prepare("SELECT profile_image, bio, first_name, last_name FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$profile_image = !empty($user['profile_image']) ? $user['profile_image'] : 'default-avatrg.pmg';
$bio = $user['bio'] ?? 'No bio available.';
$full_name = isset($user['first_name'], $user['last_name']) ? $user['first_name'] . ' ' . $user['last_name'] : 'Guest User';

?>

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
                <!-- <button class="bg-purple-700 text-white p-2 rounded">For You</button> -->
                <!-- <button class="bg-gray-300 p-2 rounded">Most Recent</button> -->
                <button id="save" class="bg-purple-700 text-white p-2 rounded">Saved</button>
                <script>
                    const save = document.getElementById('save');
                    save.addEventListener('click', function(){
                        window.location.href = 'save_jobs.php';
                    });
                    </script>
                <!-- <button class="bg-gray-300 p-2 rounded">Filter Based on Talent</button> -->
            </div>
            <div class="container mx-auto p-6">
    <div class="flex flex-col md:flex-row space-x-6">
        <!-- Left Side: Saved Jobs Section -->
        <div class="md:w-2/3 bg-white p-6 rounded shadow-md">
            <h2 class="text-xl font-bold mb-4">Saved Jobs</h2>

            <?php if (empty($savedJobs)): ?>
                <p class="text-gray-500">No saved jobs yet.</p>
            <?php else: ?>
                <?php foreach ($savedJobs as $job): ?>
                    <div class="bg-white p-4 rounded shadow-md mb-4">
                        <div class="flex justify-between items-center">
                            <h3 class="font-bold"><?= htmlspecialchars($job['job_title']) ?></h3>
                            <button onclick="removeSavedJob(<?= $job['id'] ?>)" class="bg-red-500 text-white px-4 py-2 rounded">
                                Remove
                            </button>
                        </div>
                        <p class="text-gray-700">Budget: $<?= htmlspecialchars($job['budget']) ?> | PHP</p>
                        <p class="text-gray-500">Description: <?= nl2br(htmlspecialchars($job['job_description'])) ?></p>
                        <p class="text-sm text-gray-500">Posted by: <?= htmlspecialchars($job['first_name']) ?></p>
                        <p class="text-sm text-gray-500">Posted at: <?= htmlspecialchars($job['post_date']) ?></p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Right Side: Profile Section -->
        <div class="md:w-1/3 bg-white p-6 rounded shadow-md">
            <div class="text-center">
                <img src="/uploads/<?= htmlspecialchars($profile_image); ?>" class="w-24 h-24 mx-auto rounded-full object-cover">
                <h3 class="font-bold mt-2"><?= htmlspecialchars($first_name) . ' ' . htmlspecialchars($last_name); ?></h3>
                <p class="text-sm mt-1"><?= htmlspecialchars($bio); ?></p>
                <button onclick="openModal()" class="bg-purple-900 text-white w-full p-2 rounded mt-2">Edit Profile</button>
                <div class="mt-2 p-2 bg-gray-100 rounded text-center">
                    <p class="text-sm font-semibold">Proposal Credits:</p>
                    <p class="text-lg font-bold text-green-600"><?= htmlspecialchars($proposal_credits); ?></p>
                </div>
                <!-- <button class="go-premium-btn bg-yellow-500 w-full p-2 rounded mt-2">GO PREMIUM</button> -->
            </div>
        </div>
    </div>
</div>

<!-- Edit Profile Modal -->
<div id="editProfileModal" class="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center hidden">
    <div class="bg-white p-6 rounded shadow-lg w-1/3">
        <form action="../../Models/job_seeker_profile.php" method="POST" enctype="multipart/form-data">
            <h2 class="text-lg font-bold mb-4">Edit Profile</h2>
            <label class="block text-sm font-medium">Profile Image:</label>
            <input type="file" name="profile_image" class="border p-2 w-full rounded mb-3">
            <input type="hidden" name="existing_profile_image" value="<?= htmlspecialchars($profile_image); ?>">
            <label class="block text-sm font-medium">Bio:</label>
            <textarea name="bio" class="border p-2 w-full rounded mb-3"><?= htmlspecialchars($bio); ?></textarea>
            <button type="submit" class="bg-blue-500 text-white p-2 w-full rounded">Save Changes</button>
        </form>
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
<!-- <script>
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
    </script> -->

    <script> 
function reactToJob(jobId, reaction) {
    console.log("Reacting to job:", jobId, "Reaction:", reaction);

    let formData = new URLSearchParams();
    formData.append('job_id', jobId);
    formData.append('reaction', reaction);

    fetch('../../Models/Reaction.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        console.log("Server Response:", data);

        if (data.success) {
            document.getElementById(`heart-count-${jobId}`).textContent = data.heart_count;
            document.getElementById(`boo-count-${jobId}`).textContent = data.boo_count;

            // Redirect to saved jobs page if reaction is 'heart'
            if (reaction === 'heart') {
                window.location.href = 'Save_jobs.php';
            }
        } else {
            alert(data.message);
        }
    })
    .catch(error => console.error('Error:', error));
}

    </script>
    
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
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</body>
</html>