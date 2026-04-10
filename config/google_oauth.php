<?php
/**
 * Google Sign-In (OAuth 2.0) helpers — Campus Green Innovation Portal
 */
if (is_file(__DIR__ . '/google_oauth_credentials.php')) {
    require_once __DIR__ . '/google_oauth_credentials.php';
} else {
    if (!defined('GOOGLE_OAUTH_CLIENT_ID')) {
        define('GOOGLE_OAUTH_CLIENT_ID', '');
    }
    if (!defined('GOOGLE_OAUTH_CLIENT_SECRET')) {
        define('GOOGLE_OAUTH_CLIENT_SECRET', '');
    }
}

function google_oauth_configured(): bool
{
    return GOOGLE_OAUTH_CLIENT_ID !== '' && GOOGLE_OAUTH_CLIENT_SECRET !== '';
}

/** Full callback URL for this installation (must match Google Console). */
function google_oauth_redirect_uri(): string
{
    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    $scheme = $https ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    $dir = rtrim(str_replace('\\', '/', dirname($script)), '/');
    return $scheme . '://' . $host . $dir . '/google_oauth_callback.php';
}

function google_oauth_authorize_url(string $state): string
{
    $params = [
        'client_id'     => GOOGLE_OAUTH_CLIENT_ID,
        'redirect_uri'  => google_oauth_redirect_uri(),
        'response_type' => 'code',
        'scope'         => 'openid email profile',
        'state'         => $state,
        'access_type'   => 'online',
        'prompt'        => 'select_account',
    ];
    return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
}

/** Exchange authorization code for tokens; returns array or null on failure */
function google_oauth_exchange_code(string $code): ?array
{
    $body = http_build_query([
        'code'          => $code,
        'client_id'     => GOOGLE_OAUTH_CLIENT_ID,
        'client_secret' => GOOGLE_OAUTH_CLIENT_SECRET,
        'redirect_uri'  => google_oauth_redirect_uri(),
        'grant_type'    => 'authorization_code',
    ]);

    $ctx = stream_context_create([
        'http' => [
            'method'  => 'POST',
            'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
            'content' => $body,
            'timeout' => 20,
        ],
    ]);

    $raw = @file_get_contents('https://oauth2.googleapis.com/token', false, $ctx);
    if ($raw === false) {
        return null;
    }
    $json = json_decode($raw, true);
    return is_array($json) ? $json : null;
}

/** Fetch OpenID userinfo with access token */
function google_oauth_userinfo(string $accessToken): ?array
{
    $ctx = stream_context_create([
        'http' => [
            'method'  => 'GET',
            'header'  => "Authorization: Bearer {$accessToken}\r\n",
            'timeout' => 20,
        ],
    ]);
    $raw = @file_get_contents('https://openidconnect.googleapis.com/v1/userinfo', false, $ctx);
    if ($raw === false) {
        return null;
    }
    $json = json_decode($raw, true);
    return is_array($json) ? $json : null;
}
