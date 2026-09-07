<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);

$base = dirname(__FILE__);
require_once $base . '/config.php';
require_once $base . '/calculate_core.php';

// Only POST allowed
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

// Must be logged in
$user = getLoggedInUser();
if (!$user) jsonResponse(['error' => 'Unauthorized'], 401);

// Parse input from POST body
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) jsonResponse(['error' => 'Invalid input'], 400);

// Call core calculation
try {
    $result = bazi_calculate($input);
    jsonResponse($result);
} catch (\Exception $e) {
    jsonResponse(['error' => $e->getMessage()], 400);
}
