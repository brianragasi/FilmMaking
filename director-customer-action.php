<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/discussions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit;
}

$director = require_director();
auth_no_store();

if (!csrf_is_valid($_POST['csrf_token'] ?? null)) {
    $_SESSION['director_customer_notice'] = ['tone' => 'error', 'message' => 'Your session expired. Refresh and try again.'];
    header('Location: director.php?customers=1#customers');
    exit;
}

$result = director_update_customer(
    $director,
    (int) ($_POST['customer_id'] ?? 0),
    (string) ($_POST['action'] ?? ''),
    (string) ($_POST['name'] ?? ''),
    (string) ($_POST['bio'] ?? ''),
    (string) ($_POST['avatar_style'] ?? 'rose'),
    (string) ($_POST['ban_reason'] ?? '')
);
$_SESSION['director_customer_notice'] = [
    'tone' => $result['ok'] ? 'success' : 'error',
    'message' => (string) $result['message'],
];

header('Location: director.php?customers=1#customers');
exit;
