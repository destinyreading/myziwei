<?php
require_once dirname(__FILE__) . '/api/config.php';
startSecureSession();
$user = getLoggedInUser();
if ($user) logActivity($user['id'], 'logout', 'Logout dari ' . getClientIP());
session_destroy();
header('Location: /bazical/index.php?msg=logged_out');
exit;
