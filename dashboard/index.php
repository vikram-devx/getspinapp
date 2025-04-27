<?php
// This file allows for pretty URLs (/dashboard instead of /dashboard.php)
// Fix the include path to ensure all includes work correctly from subdirectory
$_SERVER['PHP_SELF'] = '../dashboard.php';
chdir('..');
require_once 'dashboard.php';
?>