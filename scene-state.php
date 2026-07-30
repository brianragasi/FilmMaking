<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/scene.php';

auth_no_store();
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    echo json_encode(scene_public_payload(), JSON_UNESCAPED_SLASHES);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: GET, POST');
    echo json_encode(['ok' => false, 'message' => 'Method not allowed.']);
    exit;
}

$operator = current_user();
if (!$operator || (string) ($operator['role'] ?? '') !== 'director') {
    http_response_code(401);
    echo json_encode(['ok' => false, 'message' => 'Director sign-in required.']);
    exit;
}
$operator = refresh_authenticated_user($operator);

if (!csrf_is_valid($_POST['csrf_token'] ?? null)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'message' => 'Your director session expired. Refresh and try again.']);
    exit;
}

try {
    $state = update_scene_state((string) ($_POST['cue'] ?? ''), $operator);
    echo json_encode(['ok' => true, 'state' => scene_public_payload($state)], JSON_UNESCAPED_SLASHES);
} catch (InvalidArgumentException $error) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => $error->getMessage()]);
} catch (Throwable $error) {
    error_log('[EcoCart director] ' . $error->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'Scene control could not save that cue.']);
}
