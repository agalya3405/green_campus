<?php
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/google_oauth.php';

start_guest_session();

if (!google_oauth_configured()) {
    header('Location: login.php?error=' . urlencode('Google sign-in is not configured. Ask the administrator.'));
    exit;
}

$_SESSION['google_oauth_state'] = bin2hex(random_bytes(16));

header('Location: ' . google_oauth_authorize_url($_SESSION['google_oauth_state']));
exit;
