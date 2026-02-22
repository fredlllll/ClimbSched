<?php

declare(strict_types=1);

/**
 * Emergency migration endpoint for shared hosting without shell access.
 *
 * IMPORTANT:
 * - Set UPDATE_PASSWORD in .env (do not hardcode secrets in this file).
 * - Remove this file after running migrations.
 */

$projectRoot = dirname(__DIR__);
require $projectRoot . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable($projectRoot);
$dotenv->safeLoad();

$expectedPassword = $_ENV['UPDATE_PASSWORD'] ?? getenv('UPDATE_PASSWORD') ?: '';
$password = (string) ($_GET['password'] ?? '');

if ($expectedPassword === '') {
    http_response_code(500);
    echo 'UPDATE_PASSWORD is not configured in .env';
    exit;
}

if ($password === '') {
    http_response_code(200);
    ?>
    <!doctype html>
    <html lang="en">
    <head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Run migrations</title></head>
    <body>
      <h1>Run migrations</h1>
      <form method="get">
        <label>Password <input type="password" name="password" required></label>
        <button type="submit">Run php artisan migrate --force</button>
      </form>
      <p>This endpoint only runs <code>php artisan migrate --force --no-interaction</code>.</p>
    </body>
    </html>
    <?php
    exit;
}

if (!hash_equals($expectedPassword, $password)) {
    http_response_code(403);
    echo 'Forbidden';
    exit;
}

$artisanPath = $projectRoot . '/artisan';
$phpBinary = $_ENV['UPDATE_PHP_BINARY'] ?? getenv('UPDATE_PHP_BINARY') ?: PHP_BINARY;

$command = escapeshellarg($phpBinary)
    . ' ' . escapeshellarg($artisanPath)
    . ' migrate --force --no-interaction';

$output = [];
$returnCode = 1;
exec($command . ' 2>&1', $output, $returnCode);

header('Content-Type: text/plain; charset=utf-8');
echo "PHP binary: {$phpBinary}\n";
echo "Command: php artisan migrate --force --no-interaction\n";
echo "Exit code: {$returnCode}\n\n";
echo implode("\n", $output);
