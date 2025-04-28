<?php
// This file allows for pretty URLs (/leaderboard instead of /leaderboard.php)
// Fix the include path to ensure all includes work correctly from subdirectory
$_SERVER['PHP_SELF'] = 'leaderboard.php'; // Set to just the filename, not the relative path
chdir('..');
require_once 'leaderboard.php';
?>