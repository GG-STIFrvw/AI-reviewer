<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/vendor/autoload.php';

$apiKey = "AIzaSyDr8ivB6oix71tNFuJbGAFYn9KtwdmHs0o"; // Replace with your Gemini API key

$client = new \GuzzleHttp\Client();

$prompt = "This is a test prompt.";

try {
    $response = $client->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash-latest:generateContent?key=$apiKey", [
        "headers" => [
            "Content-Type" => "application/json",
        ],
        "json" => [
            "contents" => [
                ["parts" => [["text" => $prompt]]]
            ]
        ]
    ]);

    echo "<pre>";
    print_r(json_decode($response->getBody(), true));
    echo "</pre>";

} catch (Exception $e) {
    echo "Caught exception: " . $e->getMessage();
}
?>