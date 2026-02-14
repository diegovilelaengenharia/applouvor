<?php
/**
 * API Endpoint for Reading Plans
 * Serves reading plan data and bible book mappings.
 * 
 * Helper Logic:
 * - Loads data from `includes/reading_plan_data.json`.
 * - Supports filtering by plan ID and day.
 */

header('Content-Type: application/json; charset=utf-8');
require_once '../includes/auth.php';

// Optional: Require login if needed (referenced from avisos_api.php pattern)
// checkLogin(); // Un-comment if this should be protected

$action = $_GET['action'] ?? '';

try {
    $jsonFile = __DIR__ . '/../includes/reading_plan_data.json';
    if (!file_exists($jsonFile)) {
        throw new Exception('Data file not found.');
    }

    $jsonData = file_get_contents($jsonFile);
    $data = json_decode($jsonData, true);

    if (!$data) {
        throw new Exception('Error decoding data file.');
    }

    $response = ['success' => true];

    switch ($action) {
        case 'get_plans':
            // Return only plan metadata (exclude 'data' to save bandwidth)
            $plansMeta = [];
            foreach ($data['plans'] as $id => $plan) {
                $meta = $plan;
                unset($meta['data']); // Remove heavy data
                $plansMeta[$id] = $meta;
            }
            $response['plans'] = $plansMeta;
            break;

        case 'get_plan_details':
            $planId = $_GET['plan_id'] ?? '';
            if (empty($planId) || !isset($data['plans'][$planId])) {
                throw new Exception('Plan ID not found.');
            }
            $response['plan'] = $data['plans'][$planId];
            break;

        case 'get_books_map':
            $response['books'] = $data['books'];
            break;

        case 'get_today_reading':
            // Logic to calculate today's reading could go here if needed server-side
            // For now, just return specific plan data day
            $planId = $_GET['plan_id'] ?? '';
            $day = $_GET['day'] ?? 1;
             if (empty($planId) || !isset($data['plans'][$planId])) {
                throw new Exception('Plan ID not found.');
            }
            $planData = $data['plans'][$planId]['data'];
             // Handle if day is string or int
            $dayKey = (string)$day;
            $response['reading'] = $planData[$dayKey] ?? [];
            break;

        default:
             // Default: return everything (careful with size)
             // Better to throw error or return metadata
             $plansMeta = [];
            foreach ($data['plans'] as $id => $plan) {
                $meta = $plan;
                unset($meta['data']); 
                $plansMeta[$id] = $meta;
            }
            $response['plans'] = $plansMeta;
            $response['message'] = 'Use action=get_plan_details&plan_id=ID to get full data';
            break;
    }

    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
