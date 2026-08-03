<?php
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['name'] = 'HWU Test';
$_SESSION['role'] = 'head_warehouse_admin';

require_once __DIR__ . '/../api/get_active_announcement.php';
