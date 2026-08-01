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

$discussionIds = isset($_POST['discussion_ids']) && is_array($_POST['discussion_ids'])
    ? $_POST['discussion_ids']
    : [(int) ($_POST['discussion_id'] ?? 0)];
$requestedCount = count(array_filter(array_map('intval', $discussionIds), static fn (int $id): bool => $id > 0));
$deletedCount = delete_product_discussions($director, $discussionIds);
$deleted = $deletedCount > 0;
$_SESSION['director_notice'] = [
    'tone' => $deleted ? 'success' : 'error',
    'message' => $deleted
        ? $deletedCount . ' comment' . ($deletedCount === 1 ? '' : 's') . ' removed from the storefront.'
        : ($requestedCount > 0 ? 'The selected comments could not be removed.' : 'Select at least one comment first.'),
];

header('Location: director.php?moderation=1#moderation');
exit;
