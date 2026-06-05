<?php

use CodeIgniter\Boot;
use Config\Paths;

/*
 *---------------------------------------------------------------
 * CHECK PHP VERSION
 *---------------------------------------------------------------
 */

$minPhpVersion = '8.2'; // If you update this, don't forget to update `spark`.
if (version_compare(PHP_VERSION, $minPhpVersion, '<')) {
    $message = sprintf(
        'Your PHP version must be %s or higher to run CodeIgniter. Current version: %s',
        $minPhpVersion,
        PHP_VERSION,
    );

    header('HTTP/1.1 503 Service Unavailable.', true, 503);
    echo $message;

    exit(1);
}

/*
 *---------------------------------------------------------------
 * SET THE CURRENT DIRECTORY
 *---------------------------------------------------------------
 */

// Path to the front controller (this file)
define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR);

// Ensure the current directory is pointing to the front controller's directory
if (getcwd() . DIRECTORY_SEPARATOR !== FCPATH) {
    chdir(FCPATH);
}

/*
 *---------------------------------------------------------------
 * BOOTSTRAP THE APPLICATION
 *---------------------------------------------------------------
 * This process sets up the path constants, loads and registers
 * our autoloader, along with Composer's, loads our constants
 * and fires up an environment-specific bootstrapping.
 */

// LOAD OUR PATHS CONFIG FILE
// This is the line that might need to be changed, depending on your folder structure.
require FCPATH . '../app/Config/Paths.php';
// ^^^ Change this line if you move your application folder

$paths = new Paths();

/*
 *---------------------------------------------------------------
 * AUTO-DETECT BASE URL
 *---------------------------------------------------------------
 * Dynamically set the base URL from the incoming request so the
 * app works on any host / port (localhost, XAMPP, cPanel, etc.)
 * without touching .env or App.php.
 * Only applies when app.baseURL is empty.
 */
if (empty($_ENV['app.baseURL'] ?? '') && isset($_SERVER['HTTP_HOST'])) {
    $scheme  = (! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host    = $_SERVER['HTTP_HOST']; // includes port if non-standard
    $script  = dirname($_SERVER['SCRIPT_NAME']);
    $base    = rtrim($script, '/\\') . '/';
    // If running from /public directly the dirname will be '/', keep it clean
    if ($base === '//') { $base = '/'; }
    $_ENV['app.baseURL']    = $scheme . '://' . $host . $base;
    putenv('app.baseURL='   . $_ENV['app.baseURL']);
}

// LOAD THE FRAMEWORK BOOTSTRAP FILE
require $paths->systemDirectory . '/Boot.php';

exit(Boot::bootWeb($paths));
