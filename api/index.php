<?php
declare(strict_types=1);

// Vercel invokes this function for every request (see vercel.json). The PHP
// application itself lives in the repository root, so dispatch requests there.
$projectRoot = dirname(__DIR__);
$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$relativePath = rawurldecode(ltrim($requestPath, '/'));

// Serve existing static assets directly. Keep the resolved path inside the
// project root so a URL cannot read files outside the deployed application.
$candidate = realpath($projectRoot . DIRECTORY_SEPARATOR . $relativePath);
$rootPrefix = rtrim(realpath($projectRoot) ?: $projectRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

if (
    $candidate !== false
    && $candidate !== realpath(__FILE__)
    && str_starts_with($candidate, $rootPrefix)
    && is_file($candidate)
) {
    if (strtolower(pathinfo($candidate, PATHINFO_EXTENSION)) === 'php') {
        chdir($projectRoot);
        require $candidate;
        return;
    }

    $mimeType = function_exists('mime_content_type') ? mime_content_type($candidate) : false;
    if ($mimeType) {
        header('Content-Type: ' . $mimeType);
    }
    readfile($candidate);
    return;
}

// A request for the site root (or a route handled by the front controller)
// should load the existing dashboard entry point.
chdir($projectRoot);
require $projectRoot . DIRECTORY_SEPARATOR . 'index.php';
