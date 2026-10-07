<?php
require_once __DIR__ . '/../config/database.php';
$appConfig = require __DIR__ . '/../config/app.php';

function h($v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// URL path prefix the app is served from (e.g. '' or '/Ebeneza').
// Derived from APP_URL so admin redirects/links work in any subdirectory.
function base_path(): string {
    static $base = null;
    if ($base !== null) return $base;
    $app = require __DIR__ . '/../config/app.php';
    $path = rtrim(parse_url($app['url'], PHP_URL_PATH) ?: '', '/');
    $base = $path;
    return $base;
}

function url(string $path): string {
    return base_path() . '/' . ltrim($path, '/');
}

function json_response($data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function start_session(): void {
    if (session_status() === PHP_SESSION_ACTIVE) return;
    $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => $secure,
    ]);
    session_start();
}

function csrf_token(): string {
    start_session();
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_verify(?string $token): bool {
    start_session();
    return is_string($token) && isset($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $token);
}

// Server-side, IP-based rate limiting. The old implementation stored counters
// in the PHP session, which any client can reset by dropping the session cookie.
// Counters now live in a small JSON file per key in the system temp dir.
function rate_limit(string $key, int $maxAttempts, int $windowSeconds): bool {
    $now = time();
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'cli';
    $dir = sys_get_temp_dir() . '/ebeneza_rl';
    if (!is_dir($dir)) @mkdir($dir, 0700, true);
    $file = $dir . '/' . hash('sha256', $key . '|' . $ip) . '.json';
    $stamps = [];
    if (is_file($file)) {
        $decoded = json_decode((string)@file_get_contents($file), true);
        if (is_array($decoded)) $stamps = $decoded;
        // Auto-clean stale files so the temp dir does not grow unbounded.
        if (filemtime($file) < $now - 86400) @unlink($file);
    }
    $stamps = array_values(array_filter($stamps, fn($t) => ($now - (int)$t) < $windowSeconds));
    if (count($stamps) >= $maxAttempts) {
        @file_put_contents($file, json_encode($stamps), LOCK_EX);
        return false;
    }
    $stamps[] = $now;
    @file_put_contents($file, json_encode($stamps), LOCK_EX);
    return true;
}

function current_admin(): ?array {
    start_session();
    // Idle session expiration: force re-login after 30 minutes of inactivity.
    if (!empty($_SESSION['admin_id'])) {
        $last = (int)($_SESSION['admin_last_activity'] ?? 0);
        if ($last !== 0 && (time() - $last) > 1800) {
            $_SESSION = [];
            session_destroy();
            return null;
        }
        $_SESSION['admin_last_activity'] = time();
        $stmt = db()->prepare('SELECT id, name, email FROM admins WHERE id = ?');
        $stmt->execute([$_SESSION['admin_id']]);
        return $stmt->fetch() ?: null;
    }
    return null;
}

function require_admin(): array {
    $admin = current_admin();
    if (!$admin) {
        header('Location: ' . url('admin/login.php'));
        exit;
    }
    return $admin;
}

function require_admin_api(): array {
    $admin = current_admin();
    if (!$admin) json_response(['error' => 'Unauthorized'], 401);
    return $admin;
}

function audit_log(?int $adminId, string $action, ?string $entityType = null, ?int $entityId = null, $old = null, $new = null): void {
    try {
        $stmt = db()->prepare('INSERT INTO audit_logs (admin_id, action, entity_type, entity_id, old_value, new_value, ip_address, user_agent) VALUES (?,?,?,?,?,?,?,?)');
        $stmt->execute([
            $adminId, $action, $entityType, $entityId,
            is_string($old) ? $old : json_encode($old, JSON_UNESCAPED_UNICODE),
            is_string($new) ? $new : json_encode($new, JSON_UNESCAPED_UNICODE),
            $_SERVER['REMOTE_ADDR'] ?? null,
            substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
        ]);
    } catch (Throwable $e) {
        error_log('audit_log failed: ' . $e->getMessage());
    }
}

function send_email(string $to, string $subject, string $htmlBody): bool {
    if (empty($to) || !env('MAIL_HOST')) {
        error_log("MAIL not configured. Email to {$to} queued/failed: {$subject}");
        return false;
    }
    // Uses PHP mail() fallback; configure a proper SMTP library in production.
    $from = env('MAIL_FROM', 'no-reply@ebeneza.org');
    $headers = "From: " . env('MAIL_FROM_NAME', 'Ebeneza Foundation') . " <{$from}>\r\nContent-Type: text/html; charset=UTF-8\r\n";
    return @mail($to, $subject, $htmlBody, $headers);
}
