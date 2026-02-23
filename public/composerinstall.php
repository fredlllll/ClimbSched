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

/**
 * Downloads Composer PHAR with transport fallbacks for shared hosting.
 */
function downloadComposerPhar(string $targetPath, array &$debug): bool
{
    $url = 'https://getcomposer.org/composer-stable.phar';
    $targetDir = dirname($targetPath);

    if (!is_dir($targetDir)) {
        $debug[] = "Target directory does not exist: {$targetDir}";
        return false;
    }

    if (!is_writable($targetDir)) {
        $debug[] = "Target directory is not writable: {$targetDir}";
        return false;
    }

    $debug[] = "Download URL: {$url}";
    $debug[] = 'Trying cURL first, then file_get_contents fallback.';

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        if ($ch !== false) {
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
            curl_setopt($ch, CURLOPT_TIMEOUT, 120);
            curl_setopt($ch, CURLOPT_USERAGENT, 'ClimbSched composerinstall.php');

            $body = curl_exec($ch);
            $error = curl_error($ch);
            $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            curl_close($ch);

            if (is_string($body) && $body !== '' && $status >= 200 && $status < 300) {
                if (@file_put_contents($targetPath, $body) !== false) {
                    $debug[] = "cURL download successful (HTTP {$status}).";
                    return true;
                }

                $debug[] = 'cURL download succeeded but writing PHAR failed.';
            } else {
                $debug[] = "cURL failed (HTTP {$status}): " . ($error !== '' ? $error : 'empty response');
            }
        }
    } else {
        $debug[] = 'cURL extension not available.';
    }

    if (!filter_var(ini_get('allow_url_fopen'), FILTER_VALIDATE_BOOL)) {
        $debug[] = 'allow_url_fopen is disabled; file_get_contents fallback unavailable.';
        return false;
    }

    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'follow_location' => 1,
            'timeout' => 120,
            'header' => "User-Agent: ClimbSched composerinstall.php\r\n",
        ],
        'ssl' => [
            'verify_peer' => true,
            'verify_peer_name' => true,
        ],
    ]);

    $body = @file_get_contents($url, false, $context);
    if (!is_string($body) || $body === '') {
        $debug[] = 'file_get_contents fallback failed.';
        return false;
    }

    if (@file_put_contents($targetPath, $body) === false) {
        $debug[] = 'file_get_contents download succeeded but writing PHAR failed.';
        return false;
    }

    $debug[] = 'file_get_contents download successful.';
    return true;
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
      <p>It will use <code>COMPOSER_PHAR</code> if set; otherwise it uses <code>composer.phar</code> in the project root and runs it via PHP.</p>
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

$phpBinary = readEnvValue($projectRoot, 'PHP_BINARY');
if ($phpBinary === '') {
    $phpBinary = PHP_BINARY;
}

$composerBinary = readEnvValue($projectRoot, 'COMPOSER_PHAR');
if ($composerBinary === '') {
    $composerBinary = $projectRoot . '/composer.phar';
}

$output = [];
$downloadDebug = [];
if (str_ends_with($composerBinary, '.phar') && !is_file($composerBinary)) {
    $downloadOk = downloadComposerPhar($composerBinary, $downloadDebug);
    $output = array_merge($output, $downloadDebug, ['']);

    if (!$downloadOk && !is_file($composerBinary)) {
        $output[] = "Missing composer phar: {$composerBinary}";
        $output[] = '';
    }
}

$composerCandidates = [$composerBinary];

chdir($projectRoot);

$commandSuffix = ' install --no-interaction --no-dev --optimize-autoloader';
$returnCode = 1;
$executedCommand = '';

foreach ($composerCandidates as $candidate) {
    if (str_ends_with($candidate, '.phar') && !is_file($candidate)) {
        $returnCode = 1;
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
