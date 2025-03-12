<?php
require_once __DIR__ . '/../../config/Database.php';

use App\Config\Database;

$database = new Database();
$conn = $database->connect();

if (isset($_GET['query'])) {
    $search = '%' . $_GET['query'] . '%';

    $stmt = $conn->prepare("SELECT DISTINCT job_title FROM post_job WHERE job_title LIKE ? LIMIT 5");
    $stmt->execute([$search]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Debugging: Ipakita ang response sa browser
    if (empty($results)) {
        echo json_encode(["error" => "No results found"]);
    } else {
        echo json_encode($results);
    }
} else {
    echo json_encode(["error" => "No query provided"]);
}
?>
