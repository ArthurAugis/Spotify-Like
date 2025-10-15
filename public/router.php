<?php
// Simple router for PHP built-in server
// Serve existing files directly, otherwise fall back to Symfony front controller
if (php_sapi_name() === 'cli-server') {
    $url  = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $file = __DIR__ . $url;

    // If the requested file exists, let the built-in server serve it
    if (is_file($file)) {
        return false;
    }
}

// Fallback to the normal front controller
// Ensure the Symfony runtime sees the correct front controller filename
$_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/index.php';

// Require the front controller and return its result
return require __DIR__ . '/index.php';
