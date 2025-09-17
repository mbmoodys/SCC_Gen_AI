<?php
// Simple PHP proxy for Moody's APIs and Orbis PDFs
// Deploy this to any web hosting service

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, x-api-client, x-api-key, X-Requested-With');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Get parameters
$url = $_GET['url'] ?? $_POST['url'] ?? '';
$method = $_GET['method'] ?? $_POST['method'] ?? 'GET';
$headers = json_decode($_POST['headers'] ?? '{}', true) ?? [];

if (empty($url)) {
    http_response_code(400);
    echo json_encode(['error' => 'URL parameter is required']);
    exit();
}

// Prepare headers
$requestHeaders = [
    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
    'Accept: application/json, application/pdf, */*'
];

// Add custom headers
foreach ($headers as $key => $value) {
    $requestHeaders[] = "$key: $value";
}

// Prepare context
$contextOptions = [
    'http' => [
        'method' => $method,
        'header' => implode("\r\n", $requestHeaders),
        'timeout' => 300, // 5 minute timeout
        'follow_location' => true,
        'max_redirects' => 10
    ]
];

// Handle POST data
if ($method === 'POST' && !empty($_POST['body'])) {
    $contextOptions['http']['content'] = $_POST['body'];
}

$context = stream_context_create($contextOptions);

// Make the request
$response = file_get_contents($url, false, $context);

if ($response === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch URL']);
    exit();
}

// Get response headers
$responseHeaders = $http_response_header ?? [];
$statusCode = 200;

foreach ($responseHeaders as $header) {
    if (strpos($header, 'HTTP/') === 0) {
        preg_match('/HTTP\/\d\.\d\s+(\d+)/', $header, $matches);
        $statusCode = intval($matches[1] ?? 200);
    }
}

// Set appropriate content type
$contentType = 'application/json';
foreach ($responseHeaders as $header) {
    if (stripos($header, 'content-type:') === 0) {
        $contentType = trim(substr($header, 13));
        break;
    }
}

header('Content-Type: ' . $contentType);
http_response_code($statusCode);
echo $response;
?>
