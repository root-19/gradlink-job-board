<?php
// use App\Controllers\AuthController;
use App\Controllers\AuthController;
use Gradelink\System\Controllers\EmailController;


require_once __DIR__ . '/../../vendor/autoload.php'; 

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $auth = new AuthController();
    $email = $_GET['email'];
    $code = $_POST['code1'] . $_POST['code2'] . $_POST['code3'] . $_POST['code4'] . $_POST['code5'] . $_POST['code6'];

    $result = $auth->verifyCode($email, $code);

    if ($result['success']) {
        header("Location: login.php");
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
    <title>Verification</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="flex items-center justify-center min-h-screen bg-gray-100">
    <div class="w-full max-w-md bg-white p-8 rounded-2xl shadow-lg text-center">
        <img src="../resources/image/LOGO.png" alt="GCash Logo" class="w-20 mx-auto mb-4"> <!-- Mock GCash logo -->
        
        <h2 class="text-xl font-semibold text-gray-800">Enter Verification Code</h2>
        <p class="text-gray-500 text-sm mb-6">We sent a 6-digit code to your email.</p>
        
        <form method="POST" class="space-y-4">
            <div class="flex justify-center gap-2">
                <input type="text" name="code1" maxlength="1" class="w-12 h-12 text-center text-xl border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400" required>
                <input type="text" name="code2" maxlength="1" class="w-12 h-12 text-center text-xl border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400" required>
                <input type="text" name="code3" maxlength="1" class="w-12 h-12 text-center text-xl border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400" required>
                <input type="text" name="code4" maxlength="1" class="w-12 h-12 text-center text-xl border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400" required>
                <input type="text" name="code5" maxlength="1" class="w-12 h-12 text-center text-xl border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400" required>
                <input type="text" name="code6" maxlength="1" class="w-12 h-12 text-center text-xl border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400" required>
            </div>

            <button type="submit" class="w-full bg-blue-500 text-white py-2 rounded-lg hover:bg-blue-600 transition">Verify</button>
        </form>

        <p class="mt-4 text-sm text-gray-600">
            Didn't receive a code? 
            <a href="#" class="text-blue-500 hover:underline">Resend</a>
        </p>
    </div>
</body>
</html>

