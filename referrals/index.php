<?php
// This file allows for pretty URLs (/referrals instead of /referrals.php)
// Fix the include path to ensure all includes work correctly from subdirectory
$_SERVER['PHP_SELF'] = '../referrals.php';
chdir('..');
require_once 'referrals.php';
?>