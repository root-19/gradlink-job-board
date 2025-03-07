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

// Fetch job posts along with reactions
try {
    $stmt = $conn->prepare("SELECT p.id, p.first_name, p.job_title, p.job_description, p.budget, p.post_date, 
                            COALESCE(SUM(CASE WHEN r.reaction_type = 'heart' THEN 1 ELSE 0 END), 0) AS heart_count,
                            COALESCE(SUM(CASE WHEN r.reaction_type = 'boo' THEN 1 ELSE 0 END), 0) AS boo_count
                            FROM post_job p
                            LEFT JOIN job_reactions r ON p.id = r.job_id
                            GROUP BY p.id, p.first_name, p.job_title, p.job_description, p.budget, p.post_date
                            ORDER BY p.post_date DESC");
    $stmt->execute();
    $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching jobs: " . $e->getMessage());
}
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
        <div class="text-xl font-bold">GRADLINK</div>
        <div class="space-x-2 flex flex-wrap justify-center">
            <select class="bg-white text-black p-2 rounded">
                <option>Post Work</option>
            </select>
            <button class="bg-white text-black p-2 rounded">Hire Talent</button>
            <input type="text" id="searchTalent" placeholder="Search for Talent" class="p-2 rounded w-full md:w-auto">
<div id="suggestions" class="bg-white shadow-md rounded mt-1 absolute w-full hidden"></div>

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
                <button class="bg-purple-700 text-white p-2 rounded">Offers</button>
                <button id="seeker" class="bg-gray-300 p-2 rounded">job seeker</button>
                <script> 
              const seeker = document.getElementById('seeker');
              seeker.addEventListener('click', function() {
                window.location.href= 'seekers.php';
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

                            <div class="text-sm text-gray-500">Posted by: <?= htmlspecialchars($job['first_name']) ?></div>
                            <h2 class="font-bold">LOOKING FOR: <?= htmlspecialchars($job['job_title']) ?></h2>
                            <p class="text-sm text-gray-500">Budget: $<?= htmlspecialchars($job['budget']) ?> | PHP</p>
                            <p class="text-gray-700">Description: <?= nl2br(htmlspecialchars($job['job_description'])) ?></p>
                            <div class="text-sm text-gray-500">Posted at: <?= htmlspecialchars($job['post_date']) ?></div>

                            <div class="flex space-x-4 mt-2">
    <button class="bg-purple-900 text-white px-4 py-2 rounded" 
        onclick="viewProposal(<?= $job['id'] ?>)">
        View Proposal
    </button>
</div>
<div id="proposalModal" class="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center hidden">
    <div class="bg-white p-6 rounded-lg shadow-lg w-96">
        <h2 class="text-xl font-bold mb-4">Job Applicants</h2>
        <div id="proposalContent" class="space-y-2">
            <!-- Applicants will be loaded here -->
        </div>
        <button class="mt-4 bg-red-500 text-white px-4 py-2 rounded" onclick="closeModal()">Close</button>
    </div>
</div>
<script> 
    function viewProposal(jobId) {
    fetch(`../../Models/fetch_applicants.php?job_id=${jobId}`)
        .then(response => response.json())
        .then(data => {
            let content = document.getElementById("proposalContent");
            content.innerHTML = ""; // Clear previous data
            
            if (data.length === 0) {
                content.innerHTML = "<p class='text-gray-500'>No applicants yet.</p>";
            } else {
                data.forEach(applicant => {
                    content.innerHTML += `
                        <div class="p-2 border-b">
                            <p><strong>${applicant.first_name}</strong> (User ID: ${applicant.user_id})</p>
                        </div>
                    `;
                });
            }
            
            document.getElementById("proposalModal").classList.remove("hidden");
        })
        .catch(error => console.error("Error:", error));
}

function closeModal() {
    document.getElementById("proposalModal").classList.add("hidden");
}

</script>


                        </div>
                    <?php endforeach; ?>
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
        
        <!-- Edit Profile Button -->
        <button onclick="openModal()" class="bg-blue-500 text-white w-full p-2 rounded mt-2">Edit Profile</button>

        <!-- Premium Section -->
        <button class="bg-yellow-500 w-full p-2 rounded mt-2">GO PREMIUM</button>
        <p class="text-sm mt-2">Plan: FREE</p>
        <button class="bg-gray-300 w-full p-2 rounded mt-2">Watch Ad Video to get more</button>
    </div>
</div>

<!-- Modal for Editing Profile -->
<div id="editProfileModal" class="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center hidden">
    <div class="bg-white p-6 rounded shadow-lg w-1/3">
        <h2 class="text-lg font-bold mb-4">Edit Profile</h2>
        
        <form action="../../Models/update-profile.php" method="POST" enctype="multipart/form-data">
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
</script>
    </div>



    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.getElementById("postJobBtn").addEventListener("click", function() {
    document.getElementById("jobPostModal").classList.remove("hidden");
});

document.getElementById("closeModal").addEventListener("click", function() {
    document.getElementById("jobPostModal").classList.add("hidden");
});

document.getElementById("submitJob").addEventListener("click", function() {
    let jobTitle = document.getElementById("jobTitle").value.trim();
    let jobDescription = document.getElementById("jobDescription").value.trim();
    let jobBudget = document.getElementById("jobBudget").value.trim();

    if (jobTitle === "" || jobDescription === "" || jobBudget === "") {
        Swal.fire({
            icon: "error",
            title: "Oops...",
            text: "All fields are required!"
        });
        return;
    }

    let formData = new FormData();
    formData.append("job_title", jobTitle);
    formData.append("job_description", jobDescription);
    formData.append("budget", jobBudget);

    fetch("../../Models/Post_job.php", {
        method: "POST",
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === "success") {
            Swal.fire({
                icon: "success",
                title: "Job Posted!",
                text: data.message,
                confirmButtonColor: "#3085d6",
                confirmButtonText: "OK"
            }).then(() => {
                window.location.reload();
            });
        } else {
            Swal.fire({
                icon: "error",
                title: "Oops...",
                text: data.message
            });
        }
    })
    .catch(error => console.error("Error:", error));
});
</script>
</body>
</html>
<style>
    .reaction-button {
        transition: transform 0.2s ease-in-out;
    }
    .reaction-button:hover {
        transform: scale(1.1);
    }
</style>
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
document.addEventListener("DOMContentLoaded", function () {
    const searchInput = document.getElementById("searchTalent");
    const suggestionsBox = document.getElementById("suggestions");

    searchInput.addEventListener("input", function () {
        let query = this.value.trim();

        if (query.length > 0) {
            fetch(`../../Models/search_talent.php?query=${query}`)
                .then(response => response.json())
                .then(data => {
                    suggestionsBox.innerHTML = "";
                    suggestionsBox.classList.remove("hidden");

                    if (data.length === 0) {
                        suggestionsBox.innerHTML = "<p class='p-2 text-black'>No results found</p>";
                        return;
                    }

                    data.forEach(user => {
                        let suggestionItem = document.createElement("div");
                        suggestionItem.classList.add("p-2", "hover:bg-black", "cursor-pointer");
                        suggestionItem.textContent = `${user.first_name} ${user.last_name}`;
                        suggestionItem.dataset.userId = user.id;

                        suggestionItem.addEventListener("click", function () {
                            window.location.href = `user_profile.php?id=${this.dataset.userId}`;
                        });

                        suggestionsBox.appendChild(suggestionItem);
                    });
                });
        } else {
            suggestionsBox.innerHTML = "";
            suggestionsBox.classList.add("hidden");
        }
    });

    document.addEventListener("click", function (event) {
        if (!searchInput.contains(event.target) && !suggestionsBox.contains(event.target)) {
            suggestionsBox.classList.add("hidden");
        }
    });
});
</script>
