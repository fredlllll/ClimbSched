<?php

declare(strict_types=1);

/**
 * Emergency Composer install endpoint for shared hosting without shell access.
 *
 * IMPORTANT:
 * - Set COMPOSER_INSTALL_PASSWORD or UPDATE_PASSWORD in .env.
 * - Remove this file after use.
 */

$projectRoot = dirname(__DIR__);

/**
 * Minimal .env reader that works even when vendor dependencies are missing.
 */
function readEnvValue(string $projectRoot, string $key): string
{
    $direct = $_ENV[$key] ?? getenv($key);
    if ($direct !== false && $direct !== null && $direct !== '') {
        return (string) $direct;
    }

    $envPath = $projectRoot . '/.env';
    if (!is_file($envPath) || !is_readable($envPath)) {
        return '';
    }

    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return '';
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        [$name, $value] = explode('=', $line, 2);
        if (trim($name) !== $key) {
            continue;
        }

        $value = trim($value);
        if (
            (str_starts_with($value, '"') && str_ends_with($value, '"')) ||
            (str_starts_with($value, "'") && str_ends_with($value, "'"))
        ) {
            $value = substr($value, 1, -1);
        }

        return $value;
    }

    return '';
}

$expectedPassword = readEnvValue($projectRoot, 'COMPOSER_INSTALL_PASSWORD');
if ($expectedPassword === '') {
    $expectedPassword = readEnvValue($projectRoot, 'UPDATE_PASSWORD');
}

$password = (string) ($_GET['password'] ?? '');

if ($expectedPassword === '') {
    http_response_code(500);
    echo 'COMPOSER_INSTALL_PASSWORD (or UPDATE_PASSWORD) is not configured in .env';
    exit;
}

if ($password === '') {
    http_response_code(200);
    ?>
    <!doctype html>
    <html lang="en">
    <head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Run composer install</title></head>
    <body>
      <h1>Run composer install</h1>
      <form method="get">
        <label>Password <input type="password" name="password" required></label>
        <button type="submit">Run composer install --no-interaction --no-dev --optimize-autoloader</button>
      </form>
      <p>This endpoint attempts to run <code>composer install --no-interaction --no-dev --optimize-autoloader</code>.</p>
      <p>It will use <code>UPDATE_COMPOSER_BINARY</code> if set, then <code>composer.phar</code> in project root, then plain <code>composer</code>.</p>
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

$phpBinary = readEnvValue($projectRoot, 'UPDATE_PHP_BINARY');
if ($phpBinary === '') {
    $phpBinary = PHP_BINARY;
}

$composerBinary = readEnvValue($projectRoot, 'UPDATE_COMPOSER_BINARY');
$composerCandidates = array_values(array_filter([
    $composerBinary,
    $projectRoot . '/composer.phar',
    'composer',
]));

if ($composerBinary === '' && !is_file($projectRoot . '/composer.phar')) {
    @copy('https://getcomposer.org/composer-stable.phar', $projectRoot . '/composer.phar');
}

chdir($projectRoot);

$commandSuffix = ' install --no-interaction --no-dev --optimize-autoloader';
$output = [];
$returnCode = 1;
$executedCommand = '';

foreach ($composerCandidates as $candidate) {
    if ($candidate === $projectRoot . '/composer.phar' && !is_file($candidate)) {
        continue;
    }

    $command = str_ends_with($candidate, '.phar')
        ? escapeshellarg($phpBinary) . ' ' . escapeshellarg($candidate) . $commandSuffix
        : escapeshellarg($candidate) . $commandSuffix;

    $trialOutput = [];
    $trialCode = 1;
    exec($command . ' 2>&1', $trialOutput, $trialCode);

    $output = array_merge($output, ["$ $command"], $trialOutput, ['']);
    $executedCommand = $command;

    if ($trialCode === 0) {
        $returnCode = 0;
        break;
    }

    $returnCode = $trialCode;
}

header('Content-Type: text/plain; charset=utf-8');
echo "PHP binary: {$phpBinary}\n";
echo "Composer override: " . ($composerBinary !== '' ? $composerBinary : '(none)') . "\n";
echo "Exit code: {$returnCode}\n";
echo "Last command: {$executedCommand}\n\n";
echo implode("\n", $output);
