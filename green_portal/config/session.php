<?php
function normalize_role(?string $role): string
{
    $role = strtolower(trim((string) $role));
    $allowed = ['admin', 'faculty', 'student', 'guest'];
    return in_array($role, $allowed, true) ? $role : 'guest';
}

function start_role_session(string $role): void
{
    $role = normalize_role($role);
    $name = 'green_portal_' . $role;
    if (session_status() === PHP_SESSION_ACTIVE) {
        if (session_name() === $name) {
            return;
        }
        session_write_close();
    }
    session_name($name);
    session_start();
}

function start_guest_session(): void
{
    start_role_session('guest');
}

function switch_to_role_session(string $role): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }
    $role = normalize_role($role);
    session_name('green_portal_' . $role);
    session_start();
    session_regenerate_id(true);
}

function resolve_role_from_request(array $allowedRoles, string $default = 'guest'): string
{
    $role = $_GET['role'] ?? $_POST['role'] ?? '';
    $role = strtolower(trim((string) $role));
    if (in_array($role, $allowedRoles, true)) {
        return $role;
    }
    foreach ($allowedRoles as $allowed) {
        if (isset($_COOKIE['green_portal_' . $allowed])) {
            return $allowed;
        }
    }
    return $default;
}
