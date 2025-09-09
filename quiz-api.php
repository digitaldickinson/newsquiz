<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

function loadEnv($filePath) {
    if (!file_exists($filePath)) return false;
    $env = [];
    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '#') === 0 || strpos($line, '=') === false) continue;
        list($key, $value) = explode('=', $line, 2);
        $env[trim($key)] = trim($value);
    }
    return $env;
}

$env = loadEnv(__DIR__ . '/.env');
$apiKey = $env['GEMINI_API_KEY'] ?? '';

if (empty($apiKey)) {
    http_response_code(500);
    echo json_encode(['error' => 'API key not configured']);
    exit();
}

function getCacheKey() {
    $date = date('Y-m-d');
    $period = (date('H') < 12) ? 'AM' : 'PM';
    return $date . '-' . $period;
}

function getCachePath() {
    return __DIR__ . '/cache/quiz_cache.json';
}

function loadFromCache() {
    $cachePath = getCachePath();
    if (!file_exists($cachePath)) return null;

    $cacheData = json_decode(file_get_contents($cachePath), true);
    if (!$cacheData || $cacheData['cacheKey'] !== getCacheKey()) return null;

    return $cacheData['quiz'];
}

function saveToCache($quiz) {
    $cacheDir = dirname(getCachePath());
    if (!file_exists($cacheDir)) mkdir($cacheDir, 0755, true);

    $cacheData = [
        'cacheKey' => getCacheKey(),
        'quiz' => $quiz,
        'timestamp' => time()
    ];
    file_put_contents(getCachePath(), json_encode($cacheData));
}

function generateQuiz($apiKey) {
    $prompt = "Generate a current affairs quiz for UK journalists and journalism students. Create exactly 20 multiple-choice questions covering recent news from September 2025. Include: UK National News (5 questions), International News (5 questions), Sports News (5 questions), North West England regional news (5 questions). Each question should have 4 multiple choice options, include the correct answer index (0-3), have a brief explanation, and include a credible news source name. Return in JSON format with questions array.";

    $data = [
        'contents' => [['parts' => [['text' => $prompt]]]],
        'generationConfig' => ['responseMimeType' => 'application/json']
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=' . $apiKey);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) throw new Exception('API request failed');

    $apiResponse = json_decode($response, true);
    $quizJson = $apiResponse['candidates'][0]['content']['parts'][0]['text'];
    $quiz = json_decode($quizJson, true);

    if (!$quiz || count($quiz['questions']) !== 20) {
        throw new Exception('Invalid quiz format');
    }

    return $quiz;
}

try {
    $quiz = loadFromCache();
    if ($quiz === null) {
        $quiz = generateQuiz($apiKey);
        saveToCache($quiz);
    }

    echo json_encode([
        'quiz' => $quiz,
        'meta' => [
            'generated' => date('Y-m-d H:i:s'),
            'edition' => (date('H') < 12) ? 'Morning Edition' : 'Afternoon Edition'
        ]
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to generate quiz: ' . $e->getMessage()]);
}
?>