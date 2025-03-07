<?php
session_start();

require_once __DIR__ . '/../../config/Database.php';

use App\Config\Database;
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




// Fetch Talents for Display
try {
    $stmt = $conn->prepare("SELECT * FROM talent WHERE user_id = :user_id");
    $stmt->bindParam(":user_id", $userId, PDO::PARAM_INT);
    $stmt->execute();
    $talents = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching talents: " . $e->getMessage());
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
            </div>
            <div class="bg-white p-4 rounded shadow-md mb-4 flex items-center justify-between">
    <div class="space-x-2">
        <button class="bg-purple-700 text-white p-2 rounded">All Talent</button>
        <button class="bg-gray-300 p-2 rounded">Archived Talent</button>
    </div>
 <!-- Add Talent Button -->
<button onclick="openModal()" class="bg-purple-700 p-2 text-white rounded">+ Add Talent</button>

<!-- Modal (Hidden by Default) -->
<div id="addTalentModal" class="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center hidden">
    <div class="bg-white p-6 rounded shadow-lg w-96">
        <h2 class="text-lg font-bold mb-4">Add New Talent</h2>

        <form id="talentForm" enctype="multipart/form-data">
            <input type="file" id="talentImage" class="mb-2 w-full border p-2 rounded">
            <input type="text" id="canDo" placeholder="I Can Do..." class="w-full border p-2 rounded mb-2">
            <textarea id="description" placeholder="Description" class="w-full border p-2 rounded mb-2"></textarea>
            <input type="number" id="budget" placeholder="Budget" class="w-full border p-2 rounded mb-2">
            <input type="text" id="estimatedTime" placeholder="Estimated Time (Days)" class="w-full border p-2 rounded mb-2">

            <div class="flex justify-end space-x-2">
                <button type="button" onclick="closeModal()" class="bg-gray-400 p-2 rounded">Cancel</button>
                <button type="button" onclick="submitTalent()" class="bg-purple-700 text-white p-2 rounded">Save</button>
            </div>
        </form>
    </div>
</div>


</div>
<!-- Talent List -->
<div class="bg-white p-6 rounded shadow-md">
    <h2 class="text-xl font-bold mb-4">My Talents</h2>
    <?php if (count($talents) > 0): ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php foreach ($talents as $talent): ?>
                <div class="border rounded p-4 shadow">
                <img src="/capstone17/uploads/<?php echo htmlspecialchars(basename($talent['image'])); ?>" 
     alt="Talent Image" 
     class="w-32 h-32 object-cover rounded">




                    <h3 class="text-lg font-bold"><?= $talent['can_do']; ?></h3>
                    <p class="text-gray-600"><?= $talent['description']; ?></p>
                    <p class="text-purple-700 font-semibold">Budget: <?= $talent['budget']; ?></p>
                    <p class="text-gray-500">Estimated Time: <?= $talent['estimated_time']; ?> days</p>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p class="text-gray-600">No talents added yet.</p>
    <?php endif; ?>
</div>
        </div>
        <div class="md:w-1/4 bg-white p-4 rounded shadow-md mt-4 md:mt-0">
       <div class="text-center">
        <div class="bg-gray-300 w-24 h-24 mx-auto rounded-full"></div>
        <h3 class="font-bold mt-2"><?= htmlspecialchars($first_name) . ' ' . htmlspecialchars($last_name); ?></h3>
        <p class="text-sm">Educational Attainment</p>
        
        <!-- Proposal Credits Section -->
        <div class="mt-2 p-2 bg-gray-100 rounded">
    <p class="text-sm font-semibold">Proposal Credits:</p>
    <p class="text-lg font-bold text-green-600"><?= htmlspecialchars($proposal_credits); ?></p>
            </div>

            <!-- <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> -->
            <button class="go-premium-btn bg-yellow-500 w-full p-2 rounded mt-2">GO PREMIUM</button>


    </div>
</div>

</div>

<script>
   function submitTalent() {
    let formData = new FormData();
    formData.append("image", document.getElementById("talentImage").files[0]);
    formData.append("can_do", document.getElementById("canDo").value);
    formData.append("description", document.getElementById("description").value);
    formData.append("budget", document.getElementById("budget").value);
    formData.append("estimated_time", document.getElementById("estimatedTime").value);

    fetch("../../Models/add_talent.php", {
        method: "POST",
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === "success") {
            Swal.fire({
                icon: "success",
                title: "Talent Added!",
                text: data.message,
                confirmButtonColor: "#6B46C1" // Purple color matching your buttons
            }).then(() => {
                closeModal();
                location.reload(); // Refresh to show the new talent in the list
            });
        } else {
            Swal.fire({
                icon: "error",
                title: "Oops...",
                text: data.message,
                confirmButtonColor: "#d33" // Red for errors
            });
        }
    })
    .catch(error => {
        console.error("Error:", error);
        Swal.fire({
            icon: "error",
            title: "Error",
            text: "Something went wrong! Please try again.",
            confirmButtonColor: "#d33"
        });
    });
}
function openModal() {
    document.getElementById("addTalentModal").classList.remove("hidden");
}

function closeModal() {
    document.getElementById("addTalentModal").classList.add("hidden");
}
    </script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

</body>
</html>
