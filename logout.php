<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Method not allowed.');
}

if (!csrf_is_valid($_POST['csrf_token'] ?? null)) {
    http_response_code(403);
    exit('Your session expired. Return to EcoCart and try again.');
}

sign_out_user();
header('Location: index.php');
exit;
