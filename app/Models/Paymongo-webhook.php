<?php
require_once __DIR__ . '/../Config/Database.php'; 

use App\Config\Database;

$database = new Database();
$conn = $database->connect(); 

header("Content-Type: application/json");

$rawPayload = file_get_contents("php://input");
$data = json_decode($rawPayload, true);

if (!$data || !isset($data['data']['attributes']['status'])) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Invalid request"]);
    exit;
}

$status = $data['data']['attributes']['status'];
$paymentIntentId = $data['data']['id'];

if ($status === "succeeded") {
    // Successful payment
    $query = "SELECT user_id, credits FROM payments WHERE payment_intent_id = :payment_intent_id AND status = 'pending'";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':payment_intent_id', $paymentIntentId, PDO::PARAM_STR);
    $stmt->execute();
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($payment) {
        $userId = $payment['user_id'];
        $credits = $payment['credits'];

        // Update the user's credits in proposal_credits table
        $query = "INSERT INTO proposal_credits (user_id, credits, last_reset)
                  VALUES (:user_id, :credits, CURDATE())
                  ON DUPLICATE KEY UPDATE credits = credits + :credits";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':credits', $credits, PDO::PARAM_INT);
        $stmt->execute();

        // Update the payment status to 'completed'
        $query = "UPDATE payments SET status = 'completed' WHERE payment_intent_id = :payment_intent_id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':payment_intent_id', $paymentIntentId, PDO::PARAM_STR);
        $stmt->execute();

        echo json_encode(["status" => "success", "message" => "Credits updated successfully!"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Payment record not found"]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Payment not completed"]);
}
?>
