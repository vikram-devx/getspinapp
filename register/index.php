<?php
// This file allows for pretty URLs (/register instead of /register.php)
// Fix the include path to ensure all includes work correctly from subdirectory
$_SERVER['PHP_SELF'] = '../register.php';
chdir('..');
require_once 'register.php';
?>