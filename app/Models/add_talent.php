<?php
session_start();
require_once __DIR__ . '/../Config/Database.php'; 
use App\Config\Database;

$database = new Database();
$conn = $database->connect(); 

// ✅ Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "You must be logged in to apply."]);
    exit();
}

$userId = $_SESSION['user_id']; // Get logged-in user ID
$first_name = $_SESSION['first_name'] ?? $_POST["first_name"] ?? "Unknown";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $canDo = $_POST["can_do"];
    $description = $_POST["description"];
    $budget = $_POST["budget"];
    $estimatedTime = $_POST["estimated_time"];

    // ✅ Handle Image Upload
    $imagePath = "";
    if (!empty($_FILES["image"]["name"])) {
        $uploadDir = __DIR__ . "/../uploads/";  // ✅ Ensure correct upload path

        // ✅ Ensure the folder exists
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $imageName = basename($_FILES["image"]["name"]);
        $targetFile = $uploadDir . $imageName;

        // ✅ Move file & check success
        if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFile)) {
            $imagePath = "uploads/" . $imageName;  // ✅ Save only relative path
        } else {
            echo json_encode(["status" => "error", "message" => "Image upload failed."]);
            exit();
        }
    }

    // ✅ Insert into database
    $query = "INSERT INTO talent (user_id, first_name, can_do, description, budget, estimated_time, image, post_date)
              VALUES (:user_id, :first_name, :can_do, :description, :budget, :estimated_time, :image, NOW())";

    $stmt = $conn->prepare($query);
    $stmt->bindParam(":user_id", $userId, PDO::PARAM_INT);
    $stmt->bindParam(":first_name", $first_name, PDO::PARAM_STR);
    $stmt->bindParam(":can_do", $canDo, PDO::PARAM_STR);
    $stmt->bindParam(":description", $description, PDO::PARAM_STR);
    $stmt->bindParam(":budget", $budget, PDO::PARAM_INT);
    $stmt->bindParam(":estimated_time", $estimatedTime, PDO::PARAM_STR);
    $stmt->bindParam(":image", $imagePath, PDO::PARAM_STR);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Talent added successfully!"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to add talent."]);
    }
}
?>
