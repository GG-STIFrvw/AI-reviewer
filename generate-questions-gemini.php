<?php
require __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpWord\IOFactory;
use Smalot\PdfParser\Parser;

$apiKey = "AIzaSyDr8ivB6oix71tNFuJbGAFYn9KtwdmHs0o"; // Replace with your Gemini API key

if (!isset($_FILES['file'])) {
    http_response_code(400);
    echo json_encode(["error" => "No file uploaded"]);
    exit;
}

$fileTmp = $_FILES['file']['tmp_name'];
$fileName = $_FILES['file']['name'];
$ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

$text = "";

try {
    if ($ext === "txt") {
        $text = file_get_contents($fileTmp);
    } elseif ($ext === "docx") {
        $phpWord = IOFactory::load($fileTmp);
        foreach ($phpWord->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                if (method_exists($element, "getText")) {
                    $text .= $element->getText() . " ";
                }
            }
        }
    } elseif ($ext === "pdf") {
        $parser = new Parser();
        $pdf = $parser->parseFile($fileTmp);
        $text = $pdf->getText();
    } else {
        throw new Exception("Unsupported file type");
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => "File parsing failed: " . $e->getMessage()]);
    exit;
}

$client = new \GuzzleHttp\Client();

//edit the prompt here

$prompt = "
From the following study material, generate 30 multiple-choice reviewer questions.
Add a number in each question.
Make the questions and choices ultra hard.
Each question must have 4 options (a, b, c, d) and specify the correct answer.
Format output strictly as JSON array with objects like:
[{ \"question\": \"\", \"choices\": { \"a\":\"\", \"b\":\"\", \"c\":\"\", \"d\":\"\" }, \"answer\": \"a\" }]

Text:
$text
";

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

    $body = json_decode($response->getBody(), true);
    $output = $body["candidates"][0]["content"]["parts"][0]["text"] ?? "";

    // Try to decode clean JSON
    $jsonStart = strpos($output, "[");
    $jsonEnd = strrpos($output, "]");
    if ($jsonStart !== false && $jsonEnd !== false) {
        $jsonString = substr($output, $jsonStart, $jsonEnd - $jsonStart + 1);
        $questions = json_decode($jsonString, true);
        if ($questions) {
            header("Content-Type: application/json");
            echo json_encode($questions);
            exit;
        }
    }

    throw new Exception("Invalid JSON returned by Gemini. Raw output: " . $output);
} catch (\GuzzleHttp\Exception\ClientException $e) {
    $response = $e->getResponse();
    $responseBodyAsString = $response->getBody()->getContents();
    http_response_code(500);
    echo json_encode(["error" => "Gemini API error: " . $responseBodyAsString]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => "Gemini API error: " . $e->getMessage()]);
}
