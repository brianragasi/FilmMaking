<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/products.php';

function avatar_styles(): array
{
    return [
        'rose' => ['label' => 'Rose', 'class' => 'bg-rose-600 text-white'],
        'cyan' => ['label' => 'Cyan', 'class' => 'bg-cyan-500 text-slate-950'],
        'emerald' => ['label' => 'Emerald', 'class' => 'bg-emerald-500 text-slate-950'],
        'amber' => ['label' => 'Amber', 'class' => 'bg-amber-400 text-slate-950'],
        'violet' => ['label' => 'Violet', 'class' => 'bg-violet-600 text-white'],
        'slate' => ['label' => 'Slate', 'class' => 'bg-slate-800 text-white'],
    ];
}

function avatar_class(?string $style): string
{
    $styles = avatar_styles();
    return $styles[$style]['class'] ?? $styles['rose']['class'];
}

function profile_initial(string $name): string
{
    $name = trim($name);
    return strtoupper(substr($name !== '' ? $name : 'E', 0, 1));
}

function profile_avatar_url(array $profile): ?string
{
    $path = (string) ($profile['avatar_path'] ?? '');
    return preg_match('#^uploads/profiles/[a-f0-9]{32}\.(?:jpg|png|webp)$#', $path) ? $path : null;
}

function profile_photo_directory(): string
{
    return dirname(__DIR__) . '/uploads/profiles';
}

function remove_profile_photo_file(?string $relativePath): void
{
    $filename = basename((string) $relativePath);
    if (!preg_match('/^[a-f0-9]{32}\.(?:jpg|png|webp)$/', $filename)) {
        return;
    }
    $path = profile_photo_directory() . '/' . $filename;
    if (is_file($path)) {
        @unlink($path);
    }
}

function store_profile_photo(array $file): array
{
    $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($error === UPLOAD_ERR_NO_FILE) {
        return ['ok' => true, 'path' => null, 'uploaded' => false];
    }
    if ($error !== UPLOAD_ERR_OK || !isset($file['tmp_name'])) {
        return ['ok' => false, 'message' => 'The profile picture could not be uploaded.'];
    }
    if ((int) ($file['size'] ?? 0) <= 0 || (int) $file['size'] > 2 * 1024 * 1024) {
        return ['ok' => false, 'message' => 'Choose a profile picture smaller than 2 MB.'];
    }

    $imageInfo = @getimagesize((string) $file['tmp_name']);
    $allowedTypes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $mime = is_array($imageInfo) ? (string) ($imageInfo['mime'] ?? '') : '';
    if (!$imageInfo || !isset($allowedTypes[$mime])) {
        return ['ok' => false, 'message' => 'Use a JPEG, PNG, or WebP profile picture.'];
    }
    if ((int) $imageInfo[0] < 80 || (int) $imageInfo[1] < 80 || (int) $imageInfo[0] > 5000 || (int) $imageInfo[1] > 5000) {
        return ['ok' => false, 'message' => 'Choose a picture between 80 and 5,000 pixels wide and tall.'];
    }

    $directory = profile_photo_directory();
    if (!is_dir($directory) && !@mkdir($directory, 0755, true) && !is_dir($directory)) {
        return ['ok' => false, 'message' => 'Profile picture storage is unavailable.'];
    }

    $filename = bin2hex(random_bytes(16)) . '.jpg';
    $destination = $directory . '/' . $filename;
    $saved = false;
    if (function_exists('imagecreatefromstring') && function_exists('imagejpeg')) {
        $sourceBytes = @file_get_contents((string) $file['tmp_name']);
        $source = is_string($sourceBytes) ? @imagecreatefromstring($sourceBytes) : false;
        if ($source !== false) {
            $sourceWidth = imagesx($source);
            $sourceHeight = imagesy($source);
            $cropSize = min($sourceWidth, $sourceHeight);
            $sourceX = (int) floor(($sourceWidth - $cropSize) / 2);
            $sourceY = (int) floor(($sourceHeight - $cropSize) / 2);
            $target = imagecreatetruecolor(512, 512);
            if ($target !== false) {
                $background = imagecolorallocate($target, 255, 255, 255);
                imagefill($target, 0, 0, $background);
                imagecopyresampled($target, $source, 0, 0, $sourceX, $sourceY, 512, 512, $cropSize, $cropSize);
                $saved = imagejpeg($target, $destination, 86);
                imagedestroy($target);
            }
            imagedestroy($source);
        }
    }

    if (!$saved) {
        $extension = $allowedTypes[$mime];
        $filename = bin2hex(random_bytes(16)) . '.' . $extension;
        $destination = $directory . '/' . $filename;
        $saved = is_uploaded_file((string) $file['tmp_name'])
            && move_uploaded_file((string) $file['tmp_name'], $destination);
    }

    if (!$saved) {
        return ['ok' => false, 'message' => 'The profile picture could not be processed.'];
    }
    @chmod($destination, 0644);
    return ['ok' => true, 'path' => 'uploads/profiles/' . $filename, 'uploaded' => true];
}

function discussion_schema_ready(): bool
{
    static $ready = null;
    $schemaVersion = 2;

    if (is_bool($ready)) {
        return $ready;
    }
    if ((int) ($_SESSION['discussion_schema_version'] ?? 0) >= $schemaVersion) {
        return $ready = true;
    }

    $pdo = db();
    if (!$pdo || !auth_schema_ready()) {
        return $ready = false;
    }

    try {
        $avatarColumn = $pdo->query("SHOW COLUMNS FROM ecocart_users LIKE 'avatar_style'")->fetch();
        if (!$avatarColumn) {
            $pdo->exec("ALTER TABLE ecocart_users ADD COLUMN avatar_style VARCHAR(20) NOT NULL DEFAULT 'rose'");
        }

        $bioColumn = $pdo->query("SHOW COLUMNS FROM ecocart_users LIKE 'bio'")->fetch();
        if (!$bioColumn) {
            $pdo->exec("ALTER TABLE ecocart_users ADD COLUMN bio VARCHAR(180) NOT NULL DEFAULT ''");
        }

        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS product_discussions (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                product_id INT UNSIGNED NOT NULL,
                user_id INT UNSIGNED NOT NULL,
                rating TINYINT UNSIGNED NOT NULL DEFAULT 5,
                body VARCHAR(1000) NOT NULL,
                is_deleted TINYINT(1) NOT NULL DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                deleted_at TIMESTAMP NULL DEFAULT NULL,
                deleted_by VARCHAR(160) NULL DEFAULT NULL,
                KEY discussions_product_active (product_id, is_deleted, id),
                KEY discussions_user (user_id, id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS product_discussion_reactions (
                discussion_id INT UNSIGNED NOT NULL,
                user_id INT UNSIGNED NOT NULL,
                reaction VARCHAR(16) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (discussion_id, user_id, reaction),
                KEY reactions_discussion (discussion_id, reaction),
                KEY reactions_user (user_id, discussion_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
        $_SESSION['discussion_schema_version'] = $schemaVersion;
        return $ready = true;
    } catch (Throwable $error) {
        error_log('[EcoCart discussions schema] ' . $error->getMessage());
        return $ready = false;
    }
}

function discussion_reaction_types(): array
{
    return [
        'helpful' => ['label' => 'Helpful', 'icon' => 'thumbs-up'],
        'love' => ['label' => 'Love', 'icon' => 'heart'],
        'funny' => ['label' => 'Funny', 'icon' => 'smile'],
    ];
}

function discussion_reaction_count(int $discussionId, string $reaction): int
{
    if ($discussionId <= 0 || !isset(discussion_reaction_types()[$reaction])) {
        return 0;
    }

    $statement = db()->prepare(
        'SELECT COUNT(*) FROM product_discussion_reactions
         WHERE discussion_id = :discussion_id AND reaction = :reaction'
    );
    $statement->execute(['discussion_id' => $discussionId, 'reaction' => $reaction]);
    return (int) $statement->fetchColumn();
}

function discussion_relative_time(string $createdAt): string
{
    $timestamp = strtotime($createdAt);
    if (!$timestamp) {
        return 'Recently';
    }

    $seconds = max(0, time() - $timestamp);
    if ($seconds < 60) {
        return 'Just now';
    }
    if ($seconds < 3600) {
        $minutes = (int) floor($seconds / 60);
        return $minutes . ' min ago';
    }
    if ($seconds < 86400) {
        $hours = (int) floor($seconds / 3600);
        return $hours . ' hr' . ($hours === 1 ? '' : 's') . ' ago';
    }
    if ($seconds < 604800) {
        $days = (int) floor($seconds / 86400);
        return $days . ' day' . ($days === 1 ? '' : 's') . ' ago';
    }

    return date('M j, Y', $timestamp);
}

function customer_profile(array $user): array
{
    $profile = [
        'name' => (string) ($user['name'] ?? 'EcoCart customer'),
        'email' => (string) ($user['email'] ?? ''),
        'avatar_style' => 'rose',
        'bio' => '',
        'avatar_path' => null,
        'is_banned' => 0,
        'ban_reason' => null,
    ];

    if ((int) ($user['id'] ?? 0) <= 0 || !discussion_schema_ready()) {
        return $profile;
    }

    try {
        $statement = db()->prepare(
            'SELECT name, email, avatar_style, bio, avatar_path, is_banned, ban_reason FROM ecocart_users WHERE id = :id LIMIT 1'
        );
        $statement->execute(['id' => (int) $user['id']]);
        $row = $statement->fetch();
        if ($row) {
            $profile = array_merge($profile, $row);
        }
    } catch (Throwable $error) {
        error_log('[EcoCart profile read] ' . $error->getMessage());
    }

    return $profile;
}

function update_customer_profile(
    array $user,
    string $name,
    string $bio,
    string $avatarStyle,
    ?array $photo = null,
    bool $removePhoto = false
): array
{
    $name = trim(preg_replace('/\s+/', ' ', $name) ?? '');
    $bio = trim(preg_replace('/\s+/', ' ', $bio) ?? '');
    $styles = avatar_styles();

    if ((string) ($user['role'] ?? '') !== 'customer' || (int) ($user['id'] ?? 0) <= 0) {
        return ['ok' => false, 'message' => 'Only customer profiles can be customized.'];
    }
    if (strlen($name) < 2 || strlen($name) > 120) {
        return ['ok' => false, 'message' => 'Use a display name between 2 and 120 characters.'];
    }
    if (strlen($bio) > 180) {
        return ['ok' => false, 'message' => 'Keep your profile note under 180 characters.'];
    }
    if (!isset($styles[$avatarStyle])) {
        return ['ok' => false, 'message' => 'Choose one of the available profile colors.'];
    }
    if (!discussion_schema_ready()) {
        return ['ok' => false, 'message' => 'Profile setup is temporarily unavailable.'];
    }

    $currentProfile = customer_profile($user);
    $currentPhoto = profile_avatar_url($currentProfile);
    $newPhoto = $currentPhoto;
    $photoResult = ['ok' => true, 'uploaded' => false, 'path' => null];
    if ($photo !== null) {
        $photoResult = store_profile_photo($photo);
        if (!$photoResult['ok']) {
            return $photoResult;
        }
        if (!empty($photoResult['uploaded'])) {
            $newPhoto = (string) $photoResult['path'];
        }
    }
    if ($removePhoto) {
        if (!empty($photoResult['uploaded'])) {
            remove_profile_photo_file((string) $photoResult['path']);
            $photoResult = ['ok' => true, 'uploaded' => false, 'path' => null];
        }
        $newPhoto = null;
    }

    try {
        $statement = db()->prepare(
            'UPDATE ecocart_users
             SET name = :name, bio = :bio, avatar_style = :avatar_style, avatar_path = :avatar_path
             WHERE id = :id'
        );
        $statement->execute([
            'name' => $name,
            'bio' => $bio,
            'avatar_style' => $avatarStyle,
            'avatar_path' => $newPhoto,
            'id' => (int) $user['id'],
        ]);
        $_SESSION['user']['name'] = $name;
        if (($removePhoto || !empty($photoResult['uploaded'])) && $currentPhoto && $currentPhoto !== $newPhoto) {
            remove_profile_photo_file($currentPhoto);
        }
        return ['ok' => true, 'message' => 'Your EcoCart profile is ready.'];
    } catch (Throwable $error) {
        if (!empty($photoResult['uploaded'])) {
            remove_profile_photo_file((string) $photoResult['path']);
        }
        error_log('[EcoCart profile update] ' . $error->getMessage());
        return ['ok' => false, 'message' => 'Your profile could not be saved right now.'];
    }
}

function product_discussion_summary(): array
{
    if (!discussion_schema_ready()) {
        return [];
    }

    try {
        $rows = db()->query(
            'SELECT d.product_id, COUNT(*) AS discussion_count, ROUND(AVG(d.rating), 1) AS average_rating
             FROM product_discussions d
             INNER JOIN ecocart_users u ON u.id = d.user_id AND u.is_banned = 0
             WHERE d.is_deleted = 0
             GROUP BY d.product_id'
        )->fetchAll();
        $summary = [];
        foreach ($rows as $row) {
            $summary[(int) $row['product_id']] = [
                'count' => (int) $row['discussion_count'],
                'rating' => (float) $row['average_rating'],
            ];
        }
        return $summary;
    } catch (Throwable $error) {
        return [];
    }
}

function product_discussions(int $productId, int $viewerId = 0, string $sort = 'newest', int $limit = 30): array
{
    if ($productId <= 0 || !discussion_schema_ready()) {
        return [];
    }

    $limit = max(1, min(50, $limit));
    $sortSql = match ($sort) {
        'highest' => 'd.rating DESC, d.id DESC',
        'helpful' => '(COALESCE(r.helpful_count, 0) + COALESCE(r.love_count, 0) + COALESCE(r.funny_count, 0)) DESC, d.id DESC',
        default => 'd.id DESC',
    };
    try {
        $statement = db()->prepare(
            "SELECT d.id, d.product_id, d.user_id, d.rating, d.body, d.created_at,
                    u.name AS author_name, u.avatar_style, u.avatar_path, u.bio AS author_bio,
                    COALESCE(r.helpful_count, 0) AS helpful_count,
                    COALESCE(r.love_count, 0) AS love_count,
                    COALESCE(r.funny_count, 0) AS funny_count
             FROM product_discussions d
             INNER JOIN ecocart_users u ON u.id = d.user_id
             LEFT JOIN (
                 SELECT discussion_id,
                        SUM(reaction = 'helpful') AS helpful_count,
                        SUM(reaction = 'love') AS love_count,
                        SUM(reaction = 'funny') AS funny_count
                 FROM product_discussion_reactions
                 GROUP BY discussion_id
             ) r ON r.discussion_id = d.id
             WHERE d.product_id = :product_id AND d.is_deleted = 0 AND u.is_banned = 0
             ORDER BY {$sortSql}
             LIMIT {$limit}"
        );
        $statement->execute(['product_id' => $productId]);
        $comments = $statement->fetchAll();

        $viewerReactions = [];
        if ($viewerId > 0 && $comments) {
            $reactionStatement = db()->prepare(
                'SELECT r.discussion_id, r.reaction
                 FROM product_discussion_reactions r
                 INNER JOIN product_discussions d ON d.id = r.discussion_id
                 WHERE r.user_id = :user_id AND d.product_id = :product_id AND d.is_deleted = 0'
            );
            $reactionStatement->execute(['user_id' => $viewerId, 'product_id' => $productId]);
            foreach ($reactionStatement->fetchAll() as $reactionRow) {
                $viewerReactions[(int) $reactionRow['discussion_id']][(string) $reactionRow['reaction']] = true;
            }
        }
        foreach ($comments as &$comment) {
            $comment['viewer_reactions'] = $viewerReactions[(int) $comment['id']] ?? [];
        }
        unset($comment);

        return $comments;
    } catch (Throwable $error) {
        error_log('[EcoCart discussion list] ' . $error->getMessage());
        return [];
    }
}

function product_discussion_rating_breakdown(int $productId): array
{
    $breakdown = array_fill(1, 5, 0);
    if ($productId <= 0 || !discussion_schema_ready()) {
        return $breakdown;
    }

    try {
        $statement = db()->prepare(
            'SELECT d.rating, COUNT(*) AS rating_count
             FROM product_discussions d
             INNER JOIN ecocart_users u ON u.id = d.user_id AND u.is_banned = 0
             WHERE d.product_id = :product_id AND d.is_deleted = 0
             GROUP BY d.rating'
        );
        $statement->execute(['product_id' => $productId]);
        foreach ($statement->fetchAll() as $row) {
            $rating = (int) $row['rating'];
            if (isset($breakdown[$rating])) {
                $breakdown[$rating] = (int) $row['rating_count'];
            }
        }
    } catch (Throwable $error) {
        error_log('[EcoCart rating breakdown] ' . $error->getMessage());
    }

    return $breakdown;
}

function recent_product_discussions(int $limit = 40): array
{
    if (!discussion_schema_ready()) {
        return [];
    }

    $limit = max(1, min(80, $limit));
    try {
        return db()->query(
            "SELECT d.id, d.product_id, d.user_id, d.rating, d.body, d.created_at,
                    u.name AS author_name, u.email AS author_email, u.avatar_style, u.avatar_path,
                    p.name AS product_name,
                    COALESCE(r.helpful_count, 0) AS helpful_count,
                    COALESCE(r.love_count, 0) AS love_count,
                    COALESCE(r.funny_count, 0) AS funny_count
             FROM product_discussions d
             INNER JOIN ecocart_users u ON u.id = d.user_id
             LEFT JOIN products p ON p.id = d.product_id
             LEFT JOIN (
                 SELECT discussion_id,
                        SUM(reaction = 'helpful') AS helpful_count,
                        SUM(reaction = 'love') AS love_count,
                        SUM(reaction = 'funny') AS funny_count
                 FROM product_discussion_reactions
                 GROUP BY discussion_id
             ) r ON r.discussion_id = d.id
             WHERE d.is_deleted = 0
             ORDER BY d.id DESC
             LIMIT {$limit}"
        )->fetchAll();
    } catch (Throwable $error) {
        error_log('[EcoCart director discussions] ' . $error->getMessage());
        return [];
    }
}

function director_customer_profiles(int $limit = 60): array
{
    if (!discussion_schema_ready()) {
        return [];
    }
    $limit = max(1, min(100, $limit));
    try {
        return db()->query(
            "SELECT u.id, u.name, u.email, u.avatar_style, u.bio, u.avatar_path,
                    u.is_banned, u.ban_reason, u.created_at, u.last_login_at,
                    COALESCE(d.comment_count, 0) AS comment_count,
                    COALESCE(o.order_count, 0) AS order_count
             FROM ecocart_users u
             LEFT JOIN (
                 SELECT user_id, COUNT(*) AS comment_count
                 FROM product_discussions WHERE is_deleted = 0 GROUP BY user_id
             ) d ON d.user_id = u.id
             LEFT JOIN (
                 SELECT email, COUNT(*) AS order_count
                 FROM orders GROUP BY email
             ) o ON o.email = u.email
             WHERE u.role = 'customer'
             ORDER BY u.id DESC
             LIMIT {$limit}"
        )->fetchAll();
    } catch (Throwable $error) {
        error_log('[EcoCart director customers] ' . $error->getMessage());
        return [];
    }
}

function director_update_customer(
    array $director,
    int $customerId,
    string $action,
    string $name,
    string $bio,
    string $avatarStyle,
    string $banReason
): array {
    if ((string) ($director['role'] ?? '') !== 'director' || $customerId <= 0 || !discussion_schema_ready()) {
        return ['ok' => false, 'message' => 'Customer moderation is unavailable.'];
    }

    $pdo = db();
    $lookup = $pdo->prepare(
        "SELECT id, name, bio, avatar_style, avatar_path, is_banned
         FROM ecocart_users WHERE id = :id AND role = 'customer' LIMIT 1"
    );
    $lookup->execute(['id' => $customerId]);
    $customer = $lookup->fetch();
    if (!$customer) {
        return ['ok' => false, 'message' => 'That customer account no longer exists.'];
    }

    try {
        if ($action === 'remove_photo') {
            $statement = $pdo->prepare('UPDATE ecocart_users SET avatar_path = NULL WHERE id = :id');
            $statement->execute(['id' => $customerId]);
            remove_profile_photo_file((string) ($customer['avatar_path'] ?? ''));
            return ['ok' => true, 'message' => 'The customer profile picture was removed.'];
        }
        if ($action === 'suspend') {
            $banReason = trim(preg_replace('/\s+/', ' ', $banReason) ?? '');
            if ($banReason === '' || strlen($banReason) > 180) {
                return ['ok' => false, 'message' => 'Add a suspension reason under 180 characters.'];
            }
            $statement = $pdo->prepare('UPDATE ecocart_users SET is_banned = 1, ban_reason = :reason WHERE id = :id');
            $statement->execute(['reason' => $banReason, 'id' => $customerId]);
            return ['ok' => true, 'message' => 'The customer account was suspended.'];
        }
        if ($action === 'restore') {
            $statement = $pdo->prepare('UPDATE ecocart_users SET is_banned = 0, ban_reason = NULL WHERE id = :id');
            $statement->execute(['id' => $customerId]);
            return ['ok' => true, 'message' => 'The customer account was restored.'];
        }
        if ($action !== 'save') {
            return ['ok' => false, 'message' => 'Unknown customer action.'];
        }

        $name = trim(preg_replace('/\s+/', ' ', $name) ?? '');
        $bio = trim(preg_replace('/\s+/', ' ', $bio) ?? '');
        if (strlen($name) < 2 || strlen($name) > 120 || strlen($bio) > 180 || !isset(avatar_styles()[$avatarStyle])) {
            return ['ok' => false, 'message' => 'Check the customer name, profile note, and profile color.'];
        }
        $statement = $pdo->prepare(
            'UPDATE ecocart_users SET name = :name, bio = :bio, avatar_style = :avatar_style WHERE id = :id'
        );
        $statement->execute(['name' => $name, 'bio' => $bio, 'avatar_style' => $avatarStyle, 'id' => $customerId]);
        return ['ok' => true, 'message' => 'The customer profile was updated.'];
    } catch (Throwable $error) {
        error_log('[EcoCart customer moderation] ' . $error->getMessage());
        return ['ok' => false, 'message' => 'That customer change could not be saved.'];
    }
}

function add_product_discussion(array $user, int $productId, int $rating, string $body): array
{
    $body = trim(preg_replace("/\r\n?|\n/", "\n", $body) ?? '');

    if ((string) ($user['role'] ?? '') !== 'customer' || (int) ($user['id'] ?? 0) <= 0) {
        return ['ok' => false, 'message' => 'Sign in with a registered customer account to join the discussion.'];
    }
    if (!isset(product_lookup()[$productId])) {
        return ['ok' => false, 'message' => 'That product is no longer available.'];
    }
    if ($rating < 1 || $rating > 5) {
        return ['ok' => false, 'message' => 'Choose a rating from 1 to 5 stars.'];
    }
    if (strlen($body) < 3 || strlen($body) > 1000) {
        return ['ok' => false, 'message' => 'Write between 3 and 1,000 characters.'];
    }
    if ((int) ($_SESSION['last_discussion_at'] ?? 0) > time() - 8) {
        return ['ok' => false, 'message' => 'Please wait a few seconds before posting again.'];
    }
    if (!discussion_schema_ready()) {
        return ['ok' => false, 'message' => 'Product discussions are temporarily unavailable.'];
    }

    try {
        $statement = db()->prepare(
            'INSERT INTO product_discussions (product_id, user_id, rating, body)
             VALUES (:product_id, :user_id, :rating, :body)'
        );
        $statement->execute([
            'product_id' => $productId,
            'user_id' => (int) $user['id'],
            'rating' => $rating,
            'body' => $body,
        ]);
        $_SESSION['last_discussion_at'] = time();
        return ['ok' => true, 'message' => 'Your comment is now part of this product discussion.'];
    } catch (Throwable $error) {
        error_log('[EcoCart discussion post] ' . $error->getMessage());
        return ['ok' => false, 'message' => 'Your comment could not be posted right now.'];
    }
}

function toggle_product_discussion_reaction(array $user, int $discussionId, int $productId, string $reaction): array
{
    if ((string) ($user['role'] ?? '') !== 'customer' || (int) ($user['id'] ?? 0) <= 0) {
        return ['ok' => false, 'message' => 'Sign in with a customer account to react.'];
    }
    if ($discussionId <= 0 || $productId <= 0 || !isset(discussion_reaction_types()[$reaction])) {
        return ['ok' => false, 'message' => 'That reaction is unavailable.'];
    }
    if (!discussion_schema_ready()) {
        return ['ok' => false, 'message' => 'Reactions are temporarily unavailable.'];
    }

    try {
        $pdo = db();
        $commentStatement = $pdo->prepare(
            'SELECT d.id
             FROM product_discussions d
             INNER JOIN ecocart_users u ON u.id = d.user_id AND u.is_banned = 0
             WHERE d.id = :id AND d.product_id = :product_id AND d.is_deleted = 0
             LIMIT 1'
        );
        $commentStatement->execute(['id' => $discussionId, 'product_id' => $productId]);
        if (!$commentStatement->fetch()) {
            return ['ok' => false, 'message' => 'That comment is no longer available.'];
        }

        $existingStatement = $pdo->prepare(
            'SELECT 1 FROM product_discussion_reactions
             WHERE discussion_id = :discussion_id AND user_id = :user_id AND reaction = :reaction LIMIT 1'
        );
        $parameters = [
            'discussion_id' => $discussionId,
            'user_id' => (int) $user['id'],
            'reaction' => $reaction,
        ];
        $existingStatement->execute($parameters);
        if ($existingStatement->fetchColumn()) {
            $delete = $pdo->prepare(
                'DELETE FROM product_discussion_reactions
                 WHERE discussion_id = :discussion_id AND user_id = :user_id AND reaction = :reaction'
            );
            $delete->execute($parameters);
            return [
                'ok' => true,
                'message' => 'Reaction removed.',
                'active' => false,
                'count' => discussion_reaction_count($discussionId, $reaction),
            ];
        }

        $insert = $pdo->prepare(
            'INSERT INTO product_discussion_reactions (discussion_id, user_id, reaction)
             VALUES (:discussion_id, :user_id, :reaction)'
        );
        $insert->execute($parameters);
        return [
            'ok' => true,
            'message' => 'Reaction added.',
            'active' => true,
            'count' => discussion_reaction_count($discussionId, $reaction),
        ];
    } catch (Throwable $error) {
        error_log('[EcoCart discussion reaction] ' . $error->getMessage());
        return ['ok' => false, 'message' => 'That reaction could not be saved.'];
    }
}

function delete_own_product_discussion(array $user, int $discussionId, int $productId): array
{
    if ((string) ($user['role'] ?? '') !== 'customer' || (int) ($user['id'] ?? 0) <= 0) {
        return ['ok' => false, 'message' => 'Sign in with the account that posted this comment.'];
    }
    if ($discussionId <= 0 || $productId <= 0 || !discussion_schema_ready()) {
        return ['ok' => false, 'message' => 'That comment is unavailable.'];
    }

    try {
        $statement = db()->prepare(
            'UPDATE product_discussions
             SET is_deleted = 1, deleted_at = CURRENT_TIMESTAMP, deleted_by = :deleted_by
             WHERE id = :id AND product_id = :product_id AND user_id = :user_id AND is_deleted = 0'
        );
        $statement->execute([
            'deleted_by' => (string) ($user['email'] ?? 'comment-owner'),
            'id' => $discussionId,
            'product_id' => $productId,
            'user_id' => (int) $user['id'],
        ]);
        if ($statement->rowCount() !== 1) {
            return ['ok' => false, 'message' => 'You can only delete your own active comments.'];
        }
        return ['ok' => true, 'message' => 'Your comment was deleted.'];
    } catch (Throwable $error) {
        error_log('[EcoCart owner discussion delete] ' . $error->getMessage());
        return ['ok' => false, 'message' => 'Your comment could not be deleted right now.'];
    }
}

function delete_product_discussion(array $director, int $discussionId): bool
{
    return delete_product_discussions($director, [$discussionId]) === 1;
}

function delete_product_discussions(array $director, array $discussionIds): int
{
    if ((string) ($director['role'] ?? '') !== 'director' || !discussion_schema_ready()) {
        return 0;
    }

    $discussionIds = array_values(array_unique(array_filter(
        array_map('intval', $discussionIds),
        static fn (int $id): bool => $id > 0
    )));
    $discussionIds = array_slice($discussionIds, 0, 80);
    if (!$discussionIds) {
        return 0;
    }

    try {
        $placeholders = implode(',', array_fill(0, count($discussionIds), '?'));
        $statement = db()->prepare(
            "UPDATE product_discussions
             SET is_deleted = 1, deleted_at = CURRENT_TIMESTAMP, deleted_by = ?
             WHERE id IN ({$placeholders}) AND is_deleted = 0"
        );
        $statement->execute(array_merge([(string) ($director['email'] ?? 'director')], $discussionIds));
        return $statement->rowCount();
    } catch (Throwable $error) {
        error_log('[EcoCart discussion delete] ' . $error->getMessage());
        return 0;
    }
}
