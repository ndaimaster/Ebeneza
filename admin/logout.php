<?php
require_once __DIR__ . '/../includes/helpers.php';
start_session();
$admin = current_admin();
if ($admin) audit_log((int)$admin['id'], 'logout');
$_SESSION = [];
session_destroy();
header('Location: ' . url('admin/login.php'));
