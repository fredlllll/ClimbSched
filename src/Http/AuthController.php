<?php

declare(strict_types=1);

final class AuthController
{
    public static function csrf(): void
    {
        jsonResponse(['csrf' => csrfToken()]);
    }

    public static function me(): void
    {
        $user = currentUser();
        if (!$user) {
            jsonResponse(['authenticated' => false]);

            return;
        }

        jsonResponse([
            'authenticated' => true,
            'user' => [
                'id' => (int)$user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'email_verified_at' => $user['email_verified_at'],
            ],
        ]);
    }

    public static function register(): void
    {
        $body = requestJson();
        if (!requireCsrf($body)) {
            jsonResponse(['error' => 'CSRF token mismatch'], 419);

            return;
        }

        $name = trim((string)($body['name'] ?? ''));
        $email = strtolower(trim((string)($body['email'] ?? '')));
        $password = (string)($body['password'] ?? '');

        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8) {
            jsonResponse(['error' => 'Ungültige Eingaben'], 422);

            return;
        }

        try {
            db()->prepare('INSERT INTO users (name, email, password_hash) VALUES (:name, :email, :hash)')
                ->execute(['name' => $name, 'email' => $email, 'hash' => password_hash($password, PASSWORD_DEFAULT)]);
        } catch (Throwable $exception) {
            jsonResponse(['error' => 'E-Mail existiert bereits'], 422);

            return;
        }

        $id = (int)db()->lastInsertId();
        $_SESSION['user_id'] = $id;

        $token = issueToken($id, 'email_verification_tokens');
        $link = appConfig()['base_url'] . '/verify-email?uid=' . $id . '&token=' . urlencode($token);
        logMail($email, 'Bitte bestätige deine E-Mail', 'Klicke hier: ' . $link);

        jsonResponse(['ok' => true, 'message' => 'Registriert. Bitte E-Mail bestätigen (siehe mail.log).']);
    }

    public static function login(): void
    {
        $body = requestJson();
        if (!requireCsrf($body)) {
            jsonResponse(['error' => 'CSRF token mismatch'], 419);

            return;
        }

        $email = strtolower(trim((string)($body['email'] ?? '')));
        $password = (string)($body['password'] ?? '');

        $stmt = db()->prepare('SELECT * FROM users WHERE email = :email');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            jsonResponse(['error' => 'Ungültige Login-Daten'], 401);

            return;
        }

        $_SESSION['user_id'] = (int)$user['id'];
        jsonResponse(['ok' => true]);
    }

    public static function logout(): void
    {
        $body = requestJson();
        if (!requireCsrf($body)) {
            jsonResponse(['error' => 'CSRF token mismatch'], 419);

            return;
        }

        session_destroy();
        session_start();
        jsonResponse(['ok' => true]);
    }

    public static function resendVerification(): void
    {
        $body = requestJson();
        if (!requireCsrf($body)) {
            jsonResponse(['error' => 'CSRF token mismatch'], 419);

            return;
        }

        $user = currentUser();
        if (!$user) {
            jsonResponse(['error' => 'Nicht eingeloggt'], 401);

            return;
        }

        if ($user['email_verified_at']) {
            jsonResponse(['ok' => true, 'message' => 'Bereits bestätigt']);

            return;
        }

        $token = issueToken((int)$user['id'], 'email_verification_tokens');
        $link = appConfig()['base_url'] . '/verify-email?uid=' . $user['id'] . '&token=' . urlencode($token);
        logMail($user['email'], 'Bitte bestätige deine E-Mail', 'Klicke hier: ' . $link);

        jsonResponse(['ok' => true, 'message' => 'Verifizierungslink erstellt (mail.log).']);
    }

    public static function confirmVerification(): void
    {
        $body = requestJson();
        $uid = (int)($body['uid'] ?? 0);
        $token = (string)($body['token'] ?? '');

        $user = currentUser();
        if (!$user || (int)$user['id'] !== $uid) {
            jsonResponse(['error' => 'Ungültige Anfrage'], 401);

            return;
        }

        if (!validateToken($uid, $token, 'email_verification_tokens')) {
            jsonResponse(['error' => 'Ungültiger oder abgelaufener Token'], 422);

            return;
        }

        db()->prepare('UPDATE users SET email_verified_at = NOW(), updated_at = NOW() WHERE id = :id')->execute(['id' => $uid]);
        db()->prepare('DELETE FROM email_verification_tokens WHERE user_id = :id')->execute(['id' => $uid]);

        jsonResponse(['ok' => true]);
    }

    public static function forgotPassword(): void
    {
        $body = requestJson();
        if (!requireCsrf($body)) {
            jsonResponse(['error' => 'CSRF token mismatch'], 419);

            return;
        }

        $email = strtolower(trim((string)($body['email'] ?? '')));
        $stmt = db()->prepare('SELECT * FROM users WHERE email = :email');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if ($user) {
            $token = issueToken((int)$user['id'], 'password_reset_tokens');
            $link = appConfig()['base_url'] . '/reset-password?uid=' . $user['id'] . '&token=' . urlencode($token);
            logMail($user['email'], 'Passwort zurücksetzen', 'Klicke hier: ' . $link);
        }

        jsonResponse(['ok' => true, 'message' => 'Falls die E-Mail existiert, wurde ein Link erstellt.']);
    }

    public static function resetPassword(): void
    {
        $body = requestJson();
        if (!requireCsrf($body)) {
            jsonResponse(['error' => 'CSRF token mismatch'], 419);

            return;
        }

        $uid = (int)($body['uid'] ?? 0);
        $token = (string)($body['token'] ?? '');
        $password = (string)($body['password'] ?? '');

        if ($uid <= 0 || strlen($password) < 8 || !validateToken($uid, $token, 'password_reset_tokens')) {
            jsonResponse(['error' => 'Ungültige Daten'], 422);

            return;
        }

        db()->prepare('UPDATE users SET password_hash = :hash, updated_at = NOW() WHERE id = :id')
            ->execute(['hash' => password_hash($password, PASSWORD_DEFAULT), 'id' => $uid]);
        db()->prepare('DELETE FROM password_reset_tokens WHERE user_id = :id')->execute(['id' => $uid]);

        jsonResponse(['ok' => true]);
    }
}
