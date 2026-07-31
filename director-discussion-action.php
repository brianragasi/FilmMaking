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
    $_SESSION['director_notice'] = ['tone' => 'error', 'message' => 'Your session expired. Refresh and try again.'];
    header('Location: director.php#moderation');
    exit;
}

$deleted = delete_product_discussion($director, (int) ($_POST['discussion_id'] ?? 0));
$_SESSION['director_notice'] = [
    'tone' => $deleted ? 'success' : 'error',
    'message' => $deleted ? 'The comment was removed from the storefront.' : 'That comment could not be removed.',
];

header('Location: director.php#moderation');
exit;
