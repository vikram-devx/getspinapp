<?php
/**
 * SendGrid Email Helper Class
 * This class handles sending emails using the SendGrid API
 */

class SendGridEmail {
    private $apiKey;
    private $fromEmail;
    private $fromName;
    
    /**
     * Constructor
     * 
     * @param string $apiKey - SendGrid API key
     * @param string $fromEmail - From email address
     * @param string $fromName - From name
     */
    public function __construct($apiKey, $fromEmail = 'noreply@getspins.app', $fromName = 'GetSpins App') {
        $this->apiKey = $apiKey;
        $this->fromEmail = $fromEmail;
        $this->fromName = $fromName;
    }
    
    /**
     * Send email using SendGrid API
     * 
     * @param string $toEmail - Recipient email
     * @param string $toName - Recipient name
     * @param string $subject - Email subject
     * @param string $htmlContent - HTML email content
     * @param string $plainContent - Plain text email content
     * @return array - Result with status and message
     */
    public function send($toEmail, $toName, $subject, $htmlContent, $plainContent = '') {
        // Check if API key is set
        if (empty($this->apiKey)) {
            return [
                'success' => false,
                'message' => 'SendGrid API key not set'
            ];
        }
        
        // Set plain content to stripped HTML if not provided
        if (empty($plainContent)) {
            $plainContent = strip_tags($htmlContent);
        }
        
        // Build the email payload
        $email = [
            'personalizations' => [
                [
                    'to' => [
                        [
                            'email' => $toEmail,
                            'name' => $toName
                        ]
                    ],
                    'subject' => $subject
                ]
            ],
            'from' => [
                'email' => $this->fromEmail,
                'name' => $this->fromName
            ],
            'content' => [
                [
                    'type' => 'text/plain',
                    'value' => $plainContent
                ],
                [
                    'type' => 'text/html',
                    'value' => $htmlContent
                ]
            ]
        ];
        
        // Send the request to SendGrid API
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.sendgrid.com/v3/mail/send');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($email));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json'
        ]);
        
        $response = curl_exec($ch);
        $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);
        
        // Check for errors
        if ($curl_error) {
            return [
                'success' => false,
                'message' => 'cURL Error: ' . $curl_error
            ];
        }
        
        // Successful request is a 202 status code with no body
        if ($status_code == 202) {
            return [
                'success' => true,
                'message' => 'Email sent successfully'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'SendGrid API Error: ' . $status_code . ' - ' . $response
            ];
        }
    }
    
    /**
     * Send a redemption notification email
     * 
     * @param array $redemptionData - Data about the redemption
     * @param string $toEmail - Admin email address
     * @return array - Result with status and message
     */
    public function sendRedemptionNotification($redemptionData, $toEmail) {
        $subject = 'New Reward Redemption: ' . $redemptionData['rewardName'];
        
        // Build email content
        $htmlContent = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #4e73df; color: white; padding: 15px; text-align: center; }
                .content { padding: 20px; background-color: #f8f9fc; }
                .footer { padding: 15px; text-align: center; font-size: 12px; color: #666; }
                table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
                th { background-color: #eaecf4; }
                .btn { display: inline-block; padding: 10px 15px; background-color: #4e73df; color: white; text-decoration: none; border-radius: 4px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>New Reward Redemption</h1>
                </div>
                <div class='content'>
                    <p>A user has submitted a new reward redemption request.</p>
                    
                    <table>
                        <tr>
                            <th>Redemption ID</th>
                            <td>#" . $redemptionData['redemptionId'] . "</td>
                        </tr>
                        <tr>
                            <th>User</th>
                            <td>" . htmlspecialchars($redemptionData['username']) . " (ID: " . $redemptionData['userId'] . ")</td>
                        </tr>
                        <tr>
                            <th>Reward</th>
                            <td>" . htmlspecialchars($redemptionData['rewardName']) . "</td>
                        </tr>
                        <tr>
                            <th>Points Used</th>
                            <td>" . $redemptionData['pointsUsed'] . " points</td>
                        </tr>
                        <tr>
                            <th>Redemption Time</th>
                            <td>" . date('Y-m-d H:i:s') . "</td>
                        </tr>
                    </table>";
        
        // Add redemption details if available
        if (!empty($redemptionData['redemptionDetails'])) {
            $htmlContent .= "
                    <h3>Additional Details</h3>
                    <pre>" . htmlspecialchars($redemptionData['redemptionDetails']) . "</pre>";
        }
        
        // Add admin action buttons
        $htmlContent .= "
                    <p style='text-align: center; margin-top: 30px;'>
                        <a href='" . APP_URL . "/admin/redemptions.php' class='btn'>View in Admin Panel</a>
                    </p>
                </div>
                <div class='footer'>
                    <p>This is an automated message from your GetSpins App. Please do not reply to this email.</p>
                </div>
            </div>
        </body>
        </html>";
        
        // Send the email
        return $this->send($toEmail, 'Admin', $subject, $htmlContent);
    }
}
?>