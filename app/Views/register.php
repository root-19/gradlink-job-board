<?php
use App\Controllers\AuthController;
use App\Controllers\EmailController;

require_once __DIR__ . '/../../vendor/autoload.php'; 
include_once __DIR__ . '/../Controllers/AuthController.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $auth = new AuthController();
    $emailCtrl = new EmailController();

    $firstName = $_POST['first_name'];
    $lastName = $_POST['last_name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $role = $_POST['role'];
    $verificationCode = rand(100000, 999999);

    $result = $auth->register($firstName, $lastName, $email, $password, $role);

    if ($result['success']) {
        // Store verification code in the database
        $query = "UPDATE users SET verification_code = :code WHERE email = :email";
        $stmt = $auth->getUser()->getConnection()->prepare($query);
        $stmt->bindParam(":code", $verificationCode);
        $stmt->bindParam(":email", $email);
        $stmt->execute();

        // Send email
        $emailCtrl->sendVerificationEmail($email, $verificationCode);

        header("Location: verify.php?email=" . urlencode($email));
        exit();
    } else {
        echo $result['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - GradeLink Job Hoping</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">

    <div class="bg-white rounded-lg shadow-lg max-w-lg w-full overflow-hidden">
        <!-- Hero Image -->
        <img src="../resources/image/LOGO.png" alt="Job Search" class="w-full h-40 object-cover">

        <form method="POST" class="p-6">
            <h2 class="text-2xl font-semibold mb-4 text-center text-purple-900">Register</h2>

            <!-- First & Last Name (Side by Side) -->
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="first_name" class="block text-sm font-medium text-gray-700">First Name</label>
                    <input type="text" name="first_name" id="first_name" placeholder="First Name" required class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-purple-900">
                </div>

                <div>
                    <label for="last_name" class="block text-sm font-medium text-gray-700">Last Name</label>
                    <input type="text" name="last_name" id="last_name" placeholder="Last Name" required class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-purple-900">
                </div>
            </div>

            <!-- Email Input -->
            <div class="mt-4">
                <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                <input type="email" name="email" id="email" placeholder="Email" required class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-purple-900">
            </div>

            <!-- Password Input -->
            <div class="mt-4">
                <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                <input type="password" name="password" id="password" placeholder="Password" required class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-purple-900">
            </div>

            <!-- Role Selection -->
            <div class="mt-4">
                <label for="role" class="block text-sm font-medium text-gray-700">Role</label>
                <select name="role" id="role" class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-purple-900">
                    <option value="job_seeker">Job Seeker</option>
                    <option value="employer">Employer</option>
                </select>
            </div>

            <!-- Terms & Conditions -->
            <div class="mt-4 flex items-center">
                <input type="checkbox" id="terms" name="terms" required class="w-4 h-4 text-purple-900 border-gray-300 rounded focus:ring-purple-900">
                <label for="terms" class="ml-2 text-sm text-gray-600">I agree to the <a href="#" class="text-purple-900 underline">Terms & Conditions</a></label>
            </div>

            <!-- Submit Button -->
            <div class="mt-6">
                <button type="submit" class="w-full bg-purple-900 text-white py-2 px-4 rounded-md hover:bg-purple-800 focus:outline-none focus:ring-2 focus:ring-purple-900">
                    Register
                </button>
            </div>
        </form>
    </div>

</body>
</html>
