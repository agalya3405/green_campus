<?php
require_once '../config/session.php';
start_role_session('admin');
header('Location: manage_faculty.php');
exit;
