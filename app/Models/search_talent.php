<?php

include_once __DIR__ . '/../Config/Database.php';

use App\Config\Database;

$database = new Database();
$conn = $database->connect();

if (isset($_GET['query'])) {
    $query = $_GET['query'];

    $stmt = $conn->prepare("SELECT users.id, users.first_name, users.last_name 
                            FROM users 
                            JOIN talent ON users.id = talent.user_id 
                            WHERE users.first_name LIKE :query OR users.last_name LIKE :query 
                            LIMIT 5");

    $searchTerm = "%" . $query . "%";
    $stmt->bindParam(':query', $searchTerm, PDO::PARAM_STR);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($results);
}
?>
