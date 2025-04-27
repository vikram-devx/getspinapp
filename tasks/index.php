<?php
// This file allows for pretty URLs (/tasks instead of /tasks.php)
// Fix the include path to ensure all includes work correctly from subdirectory
$_SERVER['PHP_SELF'] = '../tasks.php';
chdir('..');
require_once 'tasks.php';
?>