<?php
/**
 * Open in browser: .../green_portal/deploy_check.php
 * Delete this file after the site works (security).
 */
header('Content-Type: text/plain; charset=utf-8');
echo "PHP version: " . PHP_VERSION . "\n";
echo "index.php: " . (is_file(__DIR__ . '/index.php') ? 'ok' : 'MISSING') . "\n";
echo "config/db.php: " . (is_file(__DIR__ . '/config/db.php') ? 'ok' : 'MISSING') . "\n";
echo "config/session.php: " . (is_file(__DIR__ . '/config/session.php') ? 'ok' : 'MISSING') . "\n\n";
echo "Loading db.php (if this stops here, read the error above/below)...\n\n";
require_once __DIR__ . '/config/db.php';
echo "Database: CONNECT OK\n";
echo "\nNext: open index.php or login.php. Delete deploy_check.php when done.\n";
