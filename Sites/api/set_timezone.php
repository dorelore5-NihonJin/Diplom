<?php
session_start();
header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['offset'])) {
        throw new Exception('Offset is required');
    }
    
    $offset = (int)$input['offset'];
    $timezone = $input['timezone'] ?? 'Unknown';
    
    // Validate offset is within reasonable range (-720 to +840 minutes)
    if ($offset < -720 || $offset > 840) {
        throw new Exception('Invalid timezone offset');
    }
    
    // Store in session
    $_SESSION['user_timezone_offset'] = $offset;
    $_SESSION['user_timezone_name'] = $timezone;
    
    echo json_encode([
        'success' => true,
        'message' => 'Timezone set successfully',
        'data' => [
            'offset' => $offset,
            'timezone' => $timezone
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
