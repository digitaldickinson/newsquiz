<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// --- Configuration ---
define('CACHE_DIR', __DIR__ . '/cache');
define('CACHE_FILE', CACHE_DIR . '/quiz_cache.json');
define('PROMPT_FILE', __DIR__ . '/prompt.txt');
define('LOCK_FILE', CACHE_DIR . '/quiz_generation.lock');
define('LOCK_TIMEOUT', 120); // 2 minutes

// --- Environment Variable Loading ---
function loadEnv($filePath) {
    if (!file_exists($filePath)) return false;
    $env = [];
    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0 || strpos($line, '=') === false) continue;
        list($key, $value) = explode('=', $line, 2);
        $env[trim($key)] = trim($value);
    }
    return $env;
}

// --- Main Execution ---
$env = loadEnv(__DIR__ . '/.env');
$apiKey = $env['GEMINI_API_KEY'] ?? '';

if (empty($apiKey)) {
    http_response_code(500);
    echo json_encode(['error' => 'API key not configured on the server.']);
    exit();
}

// --- Caching Functions ---
function getCacheKey() {
    date_default_timezone_set('Europe/London');
    $date = date('Y-m-d');
    $period = (date('H') < 12) ? 'AM' : 'PM';
    return $date . '-' . $period;
}

function loadFromCache() {
    if (!file_exists(CACHE_FILE)) return null;
    $cacheContents = file_get_contents(CACHE_FILE);
    $cacheData = json_decode($cacheContents, true);

    if (json_last_error() !== JSON_ERROR_NONE || !isset($cacheData['cacheKey']) || $cacheData['cacheKey'] !== getCacheKey()) {
        return null;
    }
    return $cacheData['quiz'];
}

function saveToCache($quiz) {
    if (!is_dir(CACHE_DIR)) mkdir(CACHE_DIR, 0755, true);
    $cacheData = [
        'cacheKey' => getCacheKey(),
        'quiz' => $quiz,
        'timestamp' => time()
    ];
    file_put_contents(CACHE_FILE, json_encode($cacheData, JSON_PRETTY_PRINT));
}

// --- Quiz Generation via API ---
function generateQuiz($apiKey) {
    date_default_timezone_set('Europe/London');
    $currentDate = date('F j, Y');
    $twoDaysAgo = date('F j, Y', strtotime('-2 days'));
    $dateRange = "between {$twoDaysAgo} and {$currentDate}";

    if (!file_exists(PROMPT_FILE)) throw new Exception('Prompt file not found.');
    $promptTemplate = file_get_contents(PROMPT_FILE);
    $prompt = str_replace('{dateRange}', $dateRange, $promptTemplate);

    $data = [
        'contents' => [['parts' => [['text' => $prompt]]]],
        'tools' => [['google_search' => new stdClass()]],
        'generationConfig' => ['responseMimeType' => 'application/json']
    ];

    $ch = curl_init('https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=' . $apiKey);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT => 60,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) throw new Exception('cURL Error: ' . $error);
    if ($httpCode !== 200) throw new Exception('API request failed with HTTP code ' . $httpCode . '. Response: ' . $response);

    $apiResponse = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) throw new Exception('Failed to decode API JSON response.');
    
    $quizJson = $apiResponse['candidates'][0]['content']['parts'][0]['text'] ?? null;
    if ($quizJson === null) throw new Exception('No content found in API response.');
    
    $quiz = json_decode($quizJson, true);
    if (json_last_error() !== JSON_ERROR_NONE || !isset($quiz['questions']) || count($quiz['questions']) < 15) {
        throw new Exception('Invalid or incomplete quiz format received from API.');
    }
    return $quiz;
}

// --- API Logic ---
try {
    $quiz = loadFromCache();
    $source = 'cache';

    if ($quiz === null) {
        // --- Lock File Logic to prevent multiple simultaneous API calls ---
        if (file_exists(LOCK_FILE) && (time() - filemtime(LOCK_FILE)) < LOCK_TIMEOUT) {
            // Lock file exists and is recent, another process is generating.
            // Wait a moment, then try reading the cache again.
            sleep(5); 
            $quiz = loadFromCache();
            if ($quiz === null) {
                 // If cache is still not available after waiting, return an error to the client.
                throw new Exception("Quiz generation is in progress. Please try again in a moment.");
            }
        } else {
            // No lock file, or it's stale. Create one and generate the quiz.
            touch(LOCK_FILE);
            try {
                $quiz = generateQuiz($apiKey);
                saveToCache($quiz);
                $source = 'api';
            } finally {
                // IMPORTANT: Always remove the lock file, even if generation fails.
                if (file_exists(LOCK_FILE)) {
                    unlink(LOCK_FILE);
                }
            }
        }
    }

    echo json_encode([
        'quiz' => $quiz,
        'meta' => [
            'source' => $source,
            'generated' => date('Y-m-d H:i:s'),
            'edition' => (date('H') < 12) ? 'Morning Edition' : 'Afternoon Edition'
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to generate quiz: ' . $e->getMessage()]);
}
?>

