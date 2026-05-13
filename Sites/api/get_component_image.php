<?php
error_reporting(0);
ini_set('display_errors', 0);

require_once '../config.php';

header('Content-Type: application/json');

if (!defined('ENABLE_REMOTE_IMAGES') || !ENABLE_REMOTE_IMAGES || !defined('UNSPLASH_ACCESS_KEY') || !UNSPLASH_ACCESS_KEY) {
    echo json_encode(['error' => 'disabled']);
    exit;
}

$query = trim($_GET['q'] ?? '');
if ($query === '') {
    echo json_encode(['error' => 'missing_query']);
    exit;
}

$cacheDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'hyperpc_image_cache';
if (!is_dir($cacheDir)) {
    @mkdir($cacheDir, 0755, true);
}

$cacheKey = md5(mb_strtolower($query, 'UTF-8'));
$cacheFile = $cacheDir . DIRECTORY_SEPARATOR . $cacheKey . '.json';
$cacheTtl = 86400;

if (is_file($cacheFile) && (time() - filemtime($cacheFile) < $cacheTtl)) {
    $cached = json_decode(file_get_contents($cacheFile), true);
    if (!empty($cached['url'])) {
        echo json_encode(['url' => $cached['url'], 'cached' => true]);
        exit;
    }
}

$apiUrl = 'https://api.unsplash.com/search/photos?per_page=1&orientation=landscape&content_filter=high&query=' . urlencode($query);
$headers = [
    'Authorization: Client-ID ' . UNSPLASH_ACCESS_KEY,
    'Accept-Version: v1'
];

$response = null;

if (function_exists('curl_init')) {
    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 8);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $response = curl_exec($ch);
    curl_close($ch);
} else {
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => implode("\r\n", $headers),
            'timeout' => 8
        ]
    ]);
    $response = @file_get_contents($apiUrl, false, $context);
}

if (!$response) {
    if (function_exists('shell_exec')) {
        $headerArgs = '';
        foreach ($headers as $header) {
            $headerArgs .= ' -H ' . escapeshellarg($header);
        }
        $cmd = 'curl -s' . $headerArgs . ' ' . escapeshellarg($apiUrl);
        $response = shell_exec($cmd);
    }
}

if (!$response) {
    echo json_encode(['error' => 'request_failed']);
    exit;
}

$data = json_decode($response, true);
if (!isset($data['results'][0]['urls']['small'])) {
    echo json_encode(['error' => 'not_found', 'query' => $query]);
    exit;
}

$url = $data['results'][0]['urls']['small'];
file_put_contents($cacheFile, json_encode(['url' => $url, 'query' => $query]));

echo json_encode(['url' => $url, 'cached' => false]);
?>
