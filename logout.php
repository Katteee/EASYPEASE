
<?php
require_once __DIR__ . '/includes/auth_check.php';

destroy_user_session();

header('Location: login.php');
exit;
