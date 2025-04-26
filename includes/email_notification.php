<?php
/**
 * Email Notification Functions
 * Uses SendGrid to send email notifications
 */

/**
 * Send email notification to admin
 * 
 * @param string $subject The email subject
 * @param string $message The email message content
 * @param string $to_email Optional recipient email, if not set, will use admin email from settings
 * @return boolean True if email sent successfully, false otherwise
 */
function sendAdminEmailNotification($subject, $message, $to_email = null) {
    global $conn;
    
    // If no SendGrid API key, return early
    if (!getenv('SENDGRID_API_KEY')) {
        error_log("SendGrid API key not set");
        return false;
    }
    
    // If no recipient email specified, get admin email from settings
    if (!$to_email) {
        $stmt = $conn->prepare("SELECT value FROM admin_settings WHERE setting_key = 'admin_email'");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result && !empty($result['value'])) {
            $to_email = $result['value'];
        } else {
            error_log("Admin email not set in settings");
            return false;
        }
    }
    
    // Check if email address is valid
    if (!filter_var($to_email, FILTER_VALIDATE_EMAIL)) {
        error_log("Invalid email address: " . $to_email);
        return false;
    }
    
    try {
        // Create a temporary JS file to send the email via SendGrid API
        $template = <<<EOT
const sgMail = require('@sendgrid/mail');
sgMail.setApiKey(process.env.SENDGRID_API_KEY);

const msg = {
  to: '{$to_email}',
  from: '{$to_email}', // Using the same email as sender (must be verified in SendGrid)
  subject: '{$subject}',
  text: '{$message}',
  html: '{$message}',
};

sgMail.send(msg)
  .then(() => {
    console.log('Email sent successfully');
    process.exit(0);
  })
  .catch((error) => {
    console.error('Error sending email:', error);
    process.exit(1);
  });
EOT;

        // Save the template to a temporary file
        $tempFile = sys_get_temp_dir() . '/sendgrid_email_' . uniqid() . '.js';
        file_put_contents($tempFile, $template);
        
        // Execute the NodeJS script
        $command = "NODE_PATH=./node_modules node " . escapeshellarg($tempFile) . " 2>&1";
        exec($command, $output, $returnVar);
        
        // Clean up the temporary file
        @unlink($tempFile);
        
        // Check if email was sent successfully
        if ($returnVar === 0) {
            return true;
        } else {
            error_log("Failed to send email: " . implode("\n", $output));
            return false;
        }
    } catch (Exception $e) {
        error_log("Exception when sending email: " . $e->getMessage());
        return false;
    }
}

/**
 * Send notification about new reward redemption
 * 
 * @param int $redemption_id The redemption ID
 * @param int $user_id The user ID
 * @param string $username The username
 * @param string $reward_name The reward name
 * @param int $points_used The points used
 * @return boolean True if notification sent successfully
 */
function sendRewardRedemptionNotification($redemption_id, $user_id, $username, $reward_name, $points_used) {
    // Format points with commas
    $formatted_points = number_format($points_used);
    
    // Create a nice HTML email
    $subject = "New Reward Redemption: {$reward_name}";
    
    $message = <<<EOT
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #4e73df; color: white; padding: 10px 20px; text-align: center; }
        .content { padding: 20px; background-color: #f8f9fc; }
        .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #858796; }
        .button { display: inline-block; padding: 10px 20px; background-color: #4e73df; color: white; text-decoration: none; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>New Reward Redemption</h2>
        </div>
        <div class="content">
            <p>Hello Admin,</p>
            <p>A user has submitted a new reward redemption request:</p>
            <ul>
                <li><strong>User:</strong> {$username} (ID: {$user_id})</li>
                <li><strong>Reward:</strong> {$reward_name}</li>
                <li><strong>Points Used:</strong> {$formatted_points}</li>
                <li><strong>Redemption ID:</strong> {$redemption_id}</li>
            </ul>
            <p>Please review and process this request at your earliest convenience.</p>
            <p>
                <a href="admin/redemptions?id={$redemption_id}" class="button">View Redemption</a>
            </p>
        </div>
        <div class="footer">
            <p>This is an automated message. Please do not reply to this email.</p>
        </div>
    </div>
</body>
</html>
EOT;
    
    // Send the email notification
    return sendAdminEmailNotification($subject, $message);
}