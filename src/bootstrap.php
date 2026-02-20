<?php

declare(strict_types=1);

session_start();

$config = require __DIR__ . '/../config/config.php';

date_default_timezone_set('UTC');

function appConfig(): array
{
    global $config;

    return $config;
}

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $cfg = appConfig()['db'];
    $dsn = sprintf('pgsql:host=%s;port=%s;dbname=%s', $cfg['host'], $cfg['port'], $cfg['dbname']);
    $pdo = new PDO($dsn, $cfg['user'], $cfg['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    return $pdo;
}

function nowUtc(): DateTimeImmutable
{
    return new DateTimeImmutable('now', new DateTimeZone('UTC'));
}

function jsonResponse(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

function requestJson(): array
{
    $raw = file_get_contents('php://input') ?: '{}';
    $data = json_decode($raw, true);

    return is_array($data) ? $data : [];
}

function csrfToken(): string
{
    if (!isset($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf'];
}

function requireCsrf(array $body): bool
{
    return hash_equals($_SESSION['csrf'] ?? '', (string)($body['_csrf'] ?? ''));
}

function currentUser(): ?array
{
    $id = $_SESSION['user_id'] ?? null;
    if (!$id) {
        return null;
    }

    $stmt = db()->prepare('SELECT * FROM users WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $user = $stmt->fetch();

    return $user ?: null;
}

function issueToken(int $userId, string $table): string
{
    $token = bin2hex(random_bytes(32));
    $hash = password_hash($token, PASSWORD_DEFAULT);
    $expires = nowUtc()->modify('+1 day')->format('Y-m-d H:i:sP');

    db()->prepare("DELETE FROM {$table} WHERE user_id = :id")->execute(['id' => $userId]);
    db()->prepare("INSERT INTO {$table} (user_id, token_hash, expires_at) VALUES (:id, :hash, :expires)")
        ->execute(['id' => $userId, 'hash' => $hash, 'expires' => $expires]);

    return $token;
}

function validateToken(int $userId, string $token, string $table): bool
{
    $stmt = db()->prepare("SELECT token_hash, expires_at FROM {$table} WHERE user_id = :id ORDER BY id DESC LIMIT 1");
    $stmt->execute(['id' => $userId]);
    $row = $stmt->fetch();

    if (!$row) {
        return false;
    }

    if (new DateTimeImmutable($row['expires_at']) < nowUtc()) {
        return false;
    }

    return password_verify($token, $row['token_hash']);
}

function logMail(string $email, string $subject, string $message): void
{
    $path = appConfig()['mail_log_path'];
    $line = sprintf("[%s] To: %s | Subject: %s\n%s\n---\n", nowUtc()->format(DateTimeInterface::ATOM), $email, $subject, $message);
    file_put_contents($path, $line, FILE_APPEND);
}

function servePage(string $page): void
{
    $path = __DIR__ . '/../public/pages/' . $page;
    if (!is_file($path)) {
        http_response_code(404);
        echo 'Not found';

        return;
    }

    header('Content-Type: text/html; charset=utf-8');
    readfile($path);
}
