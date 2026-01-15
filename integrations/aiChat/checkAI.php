<?php
// /integrations/aiChat/checkAI.php

ob_start();
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

function checkOllamaStatus()
{
    $status = [
        'available' => false,
        'models' => [],
        'model_count' => 0,
        'timestamp' => date('Y-m-d H:i:s'),
        'service' => 'Ollama',
        'status' => 'Not Available',
        'error' => null
    ];

    $ch = curl_init('http://127.0.0.1:11434/api/tags');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 3,
        CURLOPT_CONNECTTIMEOUT => 2
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($http_code === 200 && $response) {
        $data = json_decode($response, true);
        if ($data && isset($data['models'])) {
            $status['available'] = true;
            $status['status'] = 'Available';
            $status['error'] = null;

            foreach ($data['models'] as $model) {
                if (isset($model['name'])) {
                    $status['models'][] = $model['name'];
                }
            }
            $status['model_count'] = count($status['models']);
        }
    } else {
        $status['error'] = $error ?: "HTTP $http_code";
    }

    return $status;
}

$ollama_status = checkOllamaStatus();

$response = [
    'success' => true,
    'ollama' => $ollama_status,
    'timestamp' => date('Y-m-d H:i:s')
];

ob_clean();
echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit;
