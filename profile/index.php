<?php
// This file allows for pretty URLs (/profile instead of /profile.php)
// Fix the include path to ensure all includes work correctly from subdirectory
$_SERVER['PHP_SELF'] = '../profile.php';
chdir('..');
require_once 'profile.php';
?>