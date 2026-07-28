<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

function start_ecocart_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $forwardedProto = strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
    $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $forwardedProto === 'https';

    session_name('ecocart_session');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $isSecure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function send_ecocart_security_headers(): void
{
    if (headers_sent()) {
        return;
    }

    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
}

start_ecocart_session();
send_ecocart_security_headers();

function auth_schema_ready(): bool
{
    static $ready = null;

    if (is_bool($ready)) {
        return $ready;
    }

    $pdo = db();
    if (!$pdo) {
        return $ready = false;
    }

    try {
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS users (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(120) NOT NULL,
                email VARCHAR(160) NOT NULL,
                password_hash VARCHAR(255) NOT NULL,
                role VARCHAR(20) NOT NULL DEFAULT 'customer',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                last_login_at TIMESTAMP NULL DEFAULT NULL,
                UNIQUE KEY users_email_unique (email)
            )"
        );
        $ready = true;
    } catch (Throwable $error) {
        $ready = false;
    }

    return $ready;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return (string) $_SESSION['csrf_token'];
}

function csrf_is_valid(?string $token): bool
{
    return is_string($token)
        && isset($_SESSION['csrf_token'])
        && hash_equals((string) $_SESSION['csrf_token'], $token);
}

function current_user(): ?array
{
    $user = $_SESSION['user'] ?? null;

    if (!is_array($user) || empty($user['id']) || empty($user['email'])) {
        return null;
    }

    return $user;
}

function login_attempt_allowed(): bool
{
    $cutoff = time() - 900;
    $attempts = array_values(array_filter(
        is_array($_SESSION['login_attempts'] ?? null) ? $_SESSION['login_attempts'] : [],
        static fn ($timestamp): bool => is_int($timestamp) && $timestamp >= $cutoff
    ));
    $_SESSION['login_attempts'] = $attempts;

    return count($attempts) < 5;
}

function record_failed_login(): void
{
    $attempts = is_array($_SESSION['login_attempts'] ?? null) ? $_SESSION['login_attempts'] : [];
    $attempts[] = time();
    $_SESSION['login_attempts'] = array_slice($attempts, -5);
}

function sign_in_user(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['user'] = [
        'id' => (int) $user['id'],
        'name' => (string) $user['name'],
        'email' => (string) $user['email'],
        'role' => (string) ($user['role'] ?? 'customer'),
    ];
    unset($_SESSION['login_attempts']);
}

function attempt_login(string $email, string $password): bool
{
    if (!login_attempt_allowed() || !auth_schema_ready()) {
        return false;
    }

    $pdo = db();
    if (!$pdo) {
        return false;
    }

    try {
        $statement = $pdo->prepare(
            'SELECT id, name, email, password_hash, role
             FROM users
             WHERE email = :email
             LIMIT 1'
        );
        $statement->execute(['email' => strtolower(trim($email))]);
        $user = $statement->fetch();

        if (!$user || !password_verify($password, (string) $user['password_hash'])) {
            record_failed_login();
            usleep(random_int(150000, 300000));
            return false;
        }

        if (password_needs_rehash((string) $user['password_hash'], PASSWORD_DEFAULT)) {
            $rehash = $pdo->prepare('UPDATE users SET password_hash = :password_hash WHERE id = :id');
            $rehash->execute([
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'id' => (int) $user['id'],
            ]);
        }

        $update = $pdo->prepare('UPDATE users SET last_login_at = CURRENT_TIMESTAMP WHERE id = :id');
        $update->execute(['id' => (int) $user['id']]);
        sign_in_user($user);
        return true;
    } catch (Throwable $error) {
        return false;
    }
}

function register_customer(string $name, string $email, string $password): array
{
    if (!auth_schema_ready()) {
        return ['ok' => false, 'message' => 'Accounts are temporarily unavailable. Please try again shortly.'];
    }

    $pdo = db();
    if (!$pdo) {
        return ['ok' => false, 'message' => 'Accounts are temporarily unavailable. Please try again shortly.'];
    }

    try {
        $statement = $pdo->prepare(
            "INSERT INTO users (name, email, password_hash, role)
             VALUES (:name, :email, :password_hash, 'customer')"
        );
        $statement->execute([
            'name' => $name,
            'email' => strtolower($email),
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        ]);

        sign_in_user([
            'id' => (int) $pdo->lastInsertId(),
            'name' => $name,
            'email' => strtolower($email),
            'role' => 'customer',
        ]);
        return ['ok' => true, 'message' => ''];
    } catch (PDOException $error) {
        if ((string) $error->getCode() === '23000') {
            return ['ok' => false, 'message' => 'An account already uses that email address.'];
        }
        return ['ok' => false, 'message' => 'Accounts are temporarily unavailable. Please try again shortly.'];
    } catch (Throwable $error) {
        return ['ok' => false, 'message' => 'Accounts are temporarily unavailable. Please try again shortly.'];
    }
}

function sign_out_user(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 42000,
            'path' => $params['path'],
            'domain' => $params['domain'],
            'secure' => (bool) $params['secure'],
            'httponly' => (bool) $params['httponly'],
            'samesite' => 'Lax',
        ]);
    }

    session_destroy();
}

function safe_next_path(?string $next): string
{
    $allowed = ['index.php', 'account.php', 'checkout.php', 'admin.php'];
    $candidate = basename((string) $next);

    return in_array($candidate, $allowed, true) ? $candidate : 'account.php';
}

function user_home(array $user): string
{
    return ($user['role'] ?? 'customer') === 'admin' ? 'admin.php' : 'account.php';
}

function require_login(): array
{
    $user = current_user();
    if ($user) {
        return $user;
    }

    $next = basename((string) ($_SERVER['PHP_SELF'] ?? 'account.php'));
    header('Location: login.php?next=' . rawurlencode(safe_next_path($next)));
    exit;
}

function require_admin(): array
{
    $user = require_login();
    $pdo = db();

    if ($pdo) {
        try {
            $statement = $pdo->prepare('SELECT id, name, email, role FROM users WHERE id = :id LIMIT 1');
            $statement->execute(['id' => (int) $user['id']]);
            $freshUser = $statement->fetch();

            if ($freshUser) {
                $_SESSION['user'] = [
                    'id' => (int) $freshUser['id'],
                    'name' => (string) $freshUser['name'],
                    'email' => (string) $freshUser['email'],
                    'role' => (string) $freshUser['role'],
                ];
                $user = $_SESSION['user'];
            } else {
                $user['role'] = 'customer';
            }
        } catch (Throwable $error) {
            $user['role'] = 'customer';
        }
    } else {
        $user['role'] = 'customer';
    }

    if (($user['role'] ?? '') !== 'admin') {
        header('Location: account.php?denied=operations');
        exit;
    }

    return $user;
}

function auth_no_store(): void
{
    header('Cache-Control: no-store, max-age=0');
    header('Pragma: no-cache');
}
