<?php
require_once 'config/session.php';
$role = $_GET['role'] ?? '';
$allowed_roles = ['admin', 'faculty', 'student'];
if (!in_array($role, $allowed_roles, true)) {
    $role = resolve_role_from_request($allowed_roles, 'guest');
}
if ($role === 'guest') {
    start_guest_session();
} else {
    start_role_session($role);
}
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
}
session_destroy();
header('Location: login.php');
exit;
