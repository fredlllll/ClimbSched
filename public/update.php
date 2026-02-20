<?php

declare(strict_types=1);

/**
 * Emergency migration endpoint for shared hosting without shell access.
 *
 * IMPORTANT:
 * - Change UPDATE_PASSWORD before uploading.
 * - Remove this file after running migrations.
 */
const UPDATE_PASSWORD = 'set-a-strong-password-here';

$password = (string)($_GET['password'] ?? '');

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

if (!hash_equals(UPDATE_PASSWORD, $password)) {
    http_response_code(403);
    echo 'Forbidden';
    exit;
}

$projectRoot = dirname(__DIR__);
$artisanPath = $projectRoot . '/artisan';
$phpBinary = PHP_BINARY;

$command = escapeshellarg($phpBinary)
    . ' ' . escapeshellarg($artisanPath)
    . ' migrate --force --no-interaction';

$output = [];
$returnCode = 1;
exec($command . ' 2>&1', $output, $returnCode);

header('Content-Type: text/plain; charset=utf-8');
echo "Command: php artisan migrate --force --no-interaction\n";
echo "Exit code: {$returnCode}\n\n";
echo implode("\n", $output);
