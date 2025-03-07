<?php  
session_start();
require_once __DIR__ . '/../Config/Database.php'; 
use App\Config\Database;

$database = new Database();
$conn = $database->connect(); 
$user_id = $_SESSION['user_id'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $bio = htmlspecialchars($_POST['bio']);
    $target_file = $_POST['existing_profile_image'] ?? 'default-avatar.png'; // Use existing image or default

    // Handle Image Upload
    if (!empty($_FILES['profile_image']['name'])) {
        $image_name = time() . "_" . basename($_FILES["profile_image"]["name"]); // Generate unique filename
        $target_dir = __DIR__ . "/../../uploads/"; // Absolute path to uploads folder
        $target_file = $target_dir . $image_name; // Full file path

        // Move uploaded file to uploads directory
        if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file)) {
            $target_file = $image_name; // Store only the filename in the database

            // Optional: Delete old image if not the default one
            if (!empty($_POST['existing_profile_image']) && $_POST['existing_profile_image'] !== 'default-avatar.png') {
                $old_image_path = __DIR__ . "/../../uploads/" . $_POST['existing_profile_image'];
                if (file_exists($old_image_path)) {
                    unlink($old_image_path);
                }
            }
        } else {
            // If upload fails, keep the old image
            $target_file = $_POST['existing_profile_image'] ?? 'default-avatar.png';
        }
    }

    // Update database with new profile image and bio
    $stmt = $conn->prepare("UPDATE users SET profile_image = ?, bio = ? WHERE id = ?");
    $stmt->execute([$target_file, $bio, $user_id]);

    // Redirect to dashboard
    header("Location: ../Views/employer/Dashboard.php");
    exit();
}
?>
