<?php
// This file allows for pretty URLs (/rewards instead of /rewards.php)
// Fix the include path to ensure all includes work correctly from subdirectory
$_SERVER['PHP_SELF'] = '../rewards.php';
chdir('..');
require_once 'rewards.php';
?>