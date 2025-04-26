<?php
// Include configuration
require_once 'includes/config.php';
$page_title = "404 Not Found";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $site_name; ?> - Page Not Found</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .error-container {
            text-align: center;
            padding: 100px 20px;
            max-width: 600px;
            margin: 0 auto;
        }
        .error-code {
            font-size: 120px;
            font-weight: bold;
            margin-bottom: 0;
            color: #f44336;
        }
        .error-message {
            font-size: 24px;
            margin-bottom: 30px;
        }
        .back-home {
            display: inline-block;
            padding: 10px 20px;
            background-color: #4e73df;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s;
        }
        .back-home:hover {
            background-color: #2e59d9;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <h1 class="error-code">404</h1>
        <h2 class="error-message">Page Not Found</h2>
        <p>The page you are looking for might have been removed, had its name changed, or is temporarily unavailable.</p>
        <a href="/" class="back-home">Back to Home</a>
    </div>
</body>
</html>