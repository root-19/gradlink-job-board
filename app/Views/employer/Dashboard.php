<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employer') {
    header("Location: login.php");
    exit();
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
    <nav class="bg-purple-900 p-4 flex flex-wrap justify-between items-center text-white">
        <div class="text-xl font-bold">GRADLINK</div>
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
                <button class="bg-purple-700 text-white p-2 rounded">Offers</button>
                <button class="bg-gray-300 p-2 rounded">Pending Offers</button>
                <button id="postJobBtn" class="bg-purple-700 text-white p-2 rounded">Post Job +</button>

<!-- Job Posting Modal -->
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


            </div>
            <div class="bg-white p-4 rounded shadow-md mb-4">
                <h2 class="font-bold">LOOKING FOR: ______________</h2>
                <p class="text-sm text-gray-500">Estimated Budget: __ | PHP</p>
                <p class="text-gray-700">Lorem ipsum dolor sit amet, consectetur adipiscing elit...</p>
                <button class="bg-blue-500 text-white p-2 rounded">View Proposals 40</button>
                <p class="text-sm text-gray-500">Proposals: __ people</p>
            </div>
        </div>

        <div class="md:w-1/4 bg-white p-4 rounded shadow-md mt-4 md:mt-0">
            <div class="text-center">
                <div class="bg-gray-300 w-24 h-24 mx-auto rounded-full"></div>
                <h3 class="font-bold mt-2">NAME SURNAME</h3>
                <p class="text-sm">Occupation: Business Owner</p>
                <button class="bg-yellow-500 w-full p-2 rounded mt-2">GO PREMIUM</button>
                <p class="text-sm mt-2">Plan: FREE</p>
                <button class="bg-gray-300 w-full p-2 rounded mt-2">Watch Ad Video to get more</button>
            </div>
        </div>
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
