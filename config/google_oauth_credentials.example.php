<?php
/**
 * Copy this file to: google_oauth_credentials.php (same folder)
 * Fill in values from Google Cloud Console → APIs & Services → Credentials → OAuth 2.0 Client ID
 *
 * Authorized redirect URI (must match exactly):
 *   https://YOUR-DOMAIN/green_portal/google_oauth_callback.php
 *   http://localhost/green_portal/google_oauth_callback.php
 */
define('GOOGLE_OAUTH_CLIENT_ID', 'YOUR_CLIENT_ID.apps.googleusercontent.com');
define('GOOGLE_OAUTH_CLIENT_SECRET', 'YOUR_CLIENT_SECRET');
