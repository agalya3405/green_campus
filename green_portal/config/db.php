<?php
/**
 * Database connection using mysqli
 * Campus Green Innovation Portal
 */

$host = 'localhost:3307';
$user = 'root';
$pass = '';
$dbname = 'green_innovation';

$conn = mysqli_connect($host, $user, $pass, $dbname);

if (!$conn) {
    die('Database connection failed: ' . mysqli_connect_error());
}

mysqli_set_charset($conn, 'utf8mb4');
