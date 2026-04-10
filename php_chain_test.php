<?php
/**
 * Session BEFORE any output (avoids "headers already sent" warnings).
 * DELETE this file when done debugging.
 */
ini_set('display_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/config/session.php';
start_guest_session();

header('Content-Type: text/plain; charset=utf-8');
echo "Step 1: PHP " . PHP_VERSION . " OK\n";
echo "Step 2: session OK\n";

require_once __DIR__ . '/config/db.php';
echo "Step 3: db.php loaded — database OK\n";
echo "\nOpen register.php next. Delete php_chain_test.php when done.\n";
