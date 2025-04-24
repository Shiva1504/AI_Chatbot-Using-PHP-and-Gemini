<?php  

// 1. Load Environment Variables Manually
function loadEnv($path) {
    if (!file_exists($path)) return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0 || !strpos($line, '=')) continue;
        list($name, $value) = explode('=', $line, 2);
        $_ENV[trim($name)] = trim(trim($value), '"\''); // remove quotes
    }
}

loadEnv(__DIR__ . '/.env');
$apiKey = $_ENV['GEMINI_API_KEY'] ?? null;

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

// 2. Validate API key
if (!$apiKey) {
    echo json_encode(['error' => 'API key not found in .env']);
    exit;
}

// 3. Prepare API URL
$url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=$apiKey";

// 4. Get input JSON
$input = json_decode(file_get_contents("php://input"), true);
if (!$input || !isset($input['message'])) {
    echo json_encode(['error' => 'Invalid input']);
    exit;
}

$user_message = trim($input['message']);

// 5. Prepare payload
$data = [
    "contents" => [
        [
            "parts" => [
                ['text' => $user_message]
            ]
        ]
    ]
];

// 6. Make cURL request
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// 7. Handle API errors
if ($http_code !== 200 || !$response) {
    echo json_encode(['error' => 'Google Gemini API error']);
    exit;
}

$response_data = json_decode($response, true);
if (!isset($response_data['candidates'][0]['content']['parts'][0]['text'])) {
    echo json_encode(['error' => 'Unexpected API response format']);
    exit;
}

// 8. Send AI Response
$ai_response = trim($response_data['candidates'][0]['content']['parts'][0]['text']);
echo json_encode(['response' => $ai_response]);
