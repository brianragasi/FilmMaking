<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/discussions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit;
}

$productId = max(0, (int) ($_POST['product_id'] ?? 0));
$returnPath = 'product.php?id=' . $productId . '#discussion';
$action = (string) ($_POST['action'] ?? 'create');
$discussionId = max(0, (int) ($_POST['discussion_id'] ?? 0));
$user = current_user();
$wantsJson = str_contains((string) ($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json')
    || strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';

if (!$user) {
    if ($wantsJson) {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'message' => 'Sign in to react.', 'login_url' => 'login.php?mode=login&next=product.php']);
        exit;
    }
    $_SESSION['discussion_notice'] = ['tone' => 'error', 'message' => 'Sign in before posting a comment.'];
    $_SESSION['last_product_id'] = $productId;
    header('Location: login.php?mode=login&next=product.php');
    exit;
}

if (!csrf_is_valid($_POST['csrf_token'] ?? null)) {
    if ($wantsJson) {
        http_response_code(419);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'message' => 'Your session expired. Refresh and try again.']);
        exit;
    }
    $_SESSION['discussion_notice'] = ['tone' => 'error', 'message' => 'Your session expired. Refresh and try again.'];
    header('Location: ' . $returnPath);
    exit;
}

$user = refresh_authenticated_user($user);
$result = match ($action) {
    'react' => toggle_product_discussion_reaction(
        $user,
        $discussionId,
        $productId,
        (string) ($_POST['reaction'] ?? '')
    ),
    'delete' => delete_own_product_discussion($user, $discussionId, $productId),
    'create' => add_product_discussion(
        $user,
        $productId,
        (int) ($_POST['rating'] ?? 0),
        (string) ($_POST['body'] ?? '')
    ),
    default => ['ok' => false, 'message' => 'Unknown discussion action.'],
};

if ($wantsJson) {
    http_response_code($result['ok'] ? 200 : 422);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array_merge($result, [
        'discussion_id' => $discussionId,
        'reaction' => (string) ($_POST['reaction'] ?? ''),
    ]));
    exit;
}

if ($action !== 'react' || !$result['ok']) {
    $_SESSION['discussion_notice'] = [
        'tone' => $result['ok'] ? 'success' : 'error',
        'message' => (string) $result['message'],
    ];
}

if ($discussionId > 0 && $action === 'react') {
    $returnPath = 'product.php?id=' . $productId . '#comment-' . $discussionId;
}

header('Location: ' . $returnPath);
exit;
