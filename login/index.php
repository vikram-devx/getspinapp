<?php
// This file allows for pretty URLs (/login instead of /login.php)
// Fix the include path to ensure all includes work correctly from subdirectory
$_SERVER['PHP_SELF'] = '../login.php';
chdir('..');
require_once 'login.php';
?>