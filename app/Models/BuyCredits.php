<?php

require_once __DIR__ . '/../../vendor/autoload.php';
use GuzzleHttp\Client;
use App\Config\Database;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['credits'])) {
    session_start();

    if (!isset($_SESSION['user_id'])) {
        echo json_encode(["status" => "error", "message" => "User not authenticated."]);
        exit;
    }

    $userId = $_SESSION['user_id'];
    $credits = (int) $_POST['credits'];

    if ($credits <= 0) {
        echo json_encode(["status" => "error", "message" => "Invalid credit amount."]);
        exit;
    }

    // Initialize database connection
    $database = new Database();
    $conn = $database->connect();

    // PayMongo API Key
    $paymongo_secret_key = 'sk_test_urk7YTwBYn1M6HJAMx9sTLCX'; // Replace with your actual key
    $amount = $credits * 10 * 100; // Convert to centavos (PHP)

    $client = new Client();

    try {
        // Request to PayMongo API to create checkout session
        $response = $client->request('POST', 'https://api.paymongo.com/v1/links', [
            'headers' => [
                'Accept' => 'application/json',
                'Authorization' => 'Basic ' . base64_encode($paymongo_secret_key . ':'),
                'Content-Type' => 'application/json',
            ],
            'json' => [
                "data" => [
                    "attributes" => [
                        "amount" => $amount,
                        "currency" => "PHP",
                        "description" => "$credits Proposal Credits Purchase",
                        "payment_method_types" => ["card", "gcash", "grab_pay"],
                        "success_url" => "https://yourwebsite.com/success",
                        "failed_url" => "https://yourwebsite.com/failed"
                    ]
                ]
            ]
        ]);

        // Parse the PayMongo API response
        $responseData = json_decode($response->getBody(), true);

        if (isset($responseData['data']['attributes']['checkout_url'])) {
            $checkoutUrl = $responseData['data']['attributes']['checkout_url'];

            // ✅ I-update agad ang proposal_credits ng user
            $stmt = $conn->prepare("UPDATE proposal_credits SET credits = credits + ? WHERE user_id = ?");
            $stmt->execute([$credits, $userId]);

            echo json_encode(["status" => "success", "payment_url" => $checkoutUrl]);
            exit;
        } else {
            echo json_encode([
                "status" => "error", 
                "message" => "Failed to get checkout URL.",
                "response" => $responseData
            ]);
            exit;
        }

    } catch (\GuzzleHttp\Exception\RequestException $e) {
        echo json_encode([
            "status" => "error", 
            "message" => "Payment processing failed.",
            "error" => $e->getMessage(), 
            "details" => $e->getResponse() ? (string) $e->getResponse()->getBody() : 'No response body'
        ]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Invalid request method or missing credits."]);
}
