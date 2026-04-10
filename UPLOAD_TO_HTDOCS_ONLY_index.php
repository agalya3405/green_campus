<?php
/**
 * Upload to htdocs and rename to: index.php
 * If your app folder is not "green_portal", change both URL lines below.
 */
$path = '/green_portal/';
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? '';
if ($host !== '') {
    header('Location: ' . $scheme . '://' . $host . $path, true, 302);
} else {
    header('Location: ' . $path, true, 302);
}
exit;
