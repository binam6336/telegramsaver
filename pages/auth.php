<?php

declare(strict_types=1);

/* =========================================================
   هسته احراز هویت — Session-based
========================================================= */

function auth_boot(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) return;

    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['SERVER_PORT'] ?? '') == 443);

    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_name('MAHYAR_SESS');
    session_start();
}

function auth_user(): ?array
{
    auth_boot();
    if (empty($_SESSION['user_id'])) return null;
    return [
        'id'       => (int)$_SESSION['user_id'],
        'username' => (string)($_SESSION['username'] ?? ''),
    ];
}

function auth_check(): bool
{
    return auth_user() !== null;
}

function auth_login(string $username, string $password): array
{
    auth_boot();

    $username = trim($username);
    if ($username === '' || $password === '') {
        return ['ok' => false, 'error' => 'نام کاربری و رمز عبور را وارد کنید'];
    }

    try {
        require_once __DIR__ . '/db.php';
        $pdo = db();
    } catch (Throwable $e) {
        return ['ok' => false, 'error' => 'اتصال به دیتابیس برقرار نشد'];
    }

    try {
        $stmt = $pdo->prepare(
            "SELECT `id`, `username`, `password_hash`, `is_active`
             FROM `users` WHERE `username` = ? LIMIT 1"
        );
        $stmt->execute([$username]);
        $user = $stmt->fetch();
    } catch (Throwable $e) {
        return ['ok' => false, 'error' => 'جدول users یافت نشد. اسکریپت SQL را اجرا کنید.'];
    }

    $fail = ['ok' => false, 'error' => 'نام کاربری یا رمز عبور نادرست است'];

    if (!$user) return $fail;
    if ((int)$user['is_active'] !== 1) {
        return ['ok' => false, 'error' => 'این حساب کاربری غیرفعال شده است'];
    }
    if (!password_verify($password, $user['password_hash'])) {
        return $fail;
    }

    if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
        try {
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE `users` SET `password_hash` = ? WHERE `id` = ?")
                ->execute([$newHash, $user['id']]);
        } catch (Throwable $e) { /* silent */
        }
    }

    session_regenerate_id(true);
    $_SESSION['user_id']      = (int)$user['id'];
    $_SESSION['username']     = (string)$user['username'];
    $_SESSION['logged_in_at'] = time();

    try {
        $pdo->prepare("UPDATE `users` SET `last_login_at` = NOW() WHERE `id` = ?")
            ->execute([$user['id']]);
    } catch (Throwable $e) { /* silent */
    }

    return ['ok' => true];
}

function auth_logout(): void
{
    auth_boot();
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $p['path'],
            $p['domain'],
            $p['secure'],
            $p['httponly']
        );
    }

    session_destroy();
}

function auth_sanitize_next(string $next): string
{
    $default = 'index.php?page=messages';
    $next = trim($next);
    if ($next === '') return $default;

    // Reject absolute URLs
    if (preg_match('~^(https?:)?//~i', $next)) return $default;
    if (preg_match('~^[a-z][a-z0-9+\-.]*:~i', $next)) return $default;

    // Only safe chars
    if (!preg_match('~^[a-zA-Z0-9_\-./?=&%#]+$~', $next)) return $default;

    // No path traversal
    if (strpos($next, '..') !== false) return $default;

    return $next;
}

function auth_require(): void
{
    if (auth_check()) return;

    $next = auth_sanitize_next((string)($_SERVER['REQUEST_URI'] ?? ''));
    header('Location: login.php?next=' . urlencode($next));
    exit;
}

function auth_require_api(): void
{
    if (auth_check()) return;

    http_response_code(401);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'status'  => 'error',
        'code'    => 'UNAUTHORIZED',
        'message' => 'برای دسترسی باید وارد شوید',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
