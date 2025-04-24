<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$auth = new Auth();

// Check if user is logged in
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$current_user = $auth->getUser();
$user_id = $current_user['id'];

// Get database connection
$db = Database::getInstance();
$conn = $db->getConnection();

// Handle task actions
$message = '';
$message_type = '';

if (isset($_GET['action'])) {
    $action = $_GET['action'];
    
    // Start a task
    if ($action === 'start' && isset($_GET['offer_id'])) {
        $offer_id = $_GET['offer_id'];
        $ip_address = $_SERVER['REMOTE_ADDR'];
        
        // Record the offer attempt
        $result = recordOfferAttempt($user_id, $offer_id, $ip_address);
        
        if ($result['status'] === 'success') {
            // For sample offers (1, 2, 3) use our built-in sample system
            if (in_array($offer_id, ['offer1', 'offer2', 'offer3'])) {
                // When using sample offers, just show a message and direct to dashboard
                $message = 'Task initiated! This is a sample task. In a real environment, you would be redirected to the actual offer. Points will be automatically credited after a moment.';
                $message_type = 'success';
                
                // Auto-credit points for sample offers after a short delay
                // In a real system, this would be handled by the postback URL
                $payout = 0;
                $offer_type = 'cpa'; // Default offer type
                
                switch ($offer_id) {
                    case 'offer1':
                        $payout = 1.5;
                        $offer_type = 'cpi'; // Set correct offer type for offer1
                        break;
                    case 'offer2':
                        $payout = 2.0;
                        $offer_type = 'cpa';
                        break;
                    case 'offer3':
                        $payout = 3.5;
                        $offer_type = 'cpa';
                        break;
                }
                
                if ($payout > 0) {
                    // Complete the offer and credit points
                    $points = (int)($payout * POINTS_CONVERSION_RATE);
                    $description = "Completed sample offer #{$offer_id}";
                    $auth->updatePoints($user_id, $points, 'earn', $description, $offer_id, 'offer');
                    
                    // Mark offer as completed
                    $stmt = $conn->prepare("UPDATE user_offers SET completed = 1, points_earned = :points, completed_at = datetime('now'), offer_type = :offer_type, payout = :payout WHERE user_id = :user_id AND offer_id = :offer_id");
                    $stmt->bindValue(':points', $points);
                    $stmt->bindValue(':user_id', $user_id);
                    $stmt->bindValue(':offer_id', $offer_id);
                    $stmt->bindValue(':offer_type', $offer_type);
                    $stmt->bindValue(':payout', $payout);
                    $stmt->execute();
                }
            } else {
                // For real API offers
                $offer_details = getOfferDetails($offer_id);
                
                if ($offer_details['status'] === 'success' && isset($offer_details['offer']) && isset($offer_details['offer']['tracking_url'])) {
                    // Append our postback parameters to the tracking URL
                    $tracking_url = $offer_details['offer']['tracking_url'];
                    $tracking_url .= (strpos($tracking_url, '?') !== false ? '&' : '?') . 'aff_sub=' . $user_id;
                    
                    // Redirect the user to the offer URL
                    header('Location: ' . $tracking_url);
                    exit;
                } else {
                    // Fallback if we couldn't get the tracking URL
                    $message = 'Could not start task. Please try again later.';
                    $message_type = 'warning';
                }
            }
        } else {
            $message = $result['message'];
            $message_type = 'danger';
        }
    }
    
    // Track task completion (this would normally be handled by the postback URL)
    if ($action === 'track' && isset($_GET['offer_id']) && isset($_GET['user_id'])) {
        // This is a simplified tracking mechanism
        // In a real implementation, this would redirect to the actual offer URL
        // and the completion would be tracked via the postback URL
        $message = 'You are being redirected to complete the offer...';
        $message_type = 'info';
    }
}

// Get database connection to check for API key
$db = Database::getInstance();
$conn = $db->getConnection();

// Get API settings
$settings = [];
try {
    $stmt = $conn->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'ogads_%'");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (PDOException $e) {
    error_log("Error retrieving API settings: " . $e->getMessage());
}

// Check if API key is configured
$api_key = isset($settings['ogads_api_key']) && !empty($settings['ogads_api_key']) 
    ? $settings['ogads_api_key'] 
    : OGADS_API_KEY;

// Get additional settings
$max_offers = isset($settings['ogads_max_offers']) ? (int)$settings['ogads_max_offers'] : 10;
$min_offers = isset($settings['ogads_min_offers']) ? (int)$settings['ogads_min_offers'] : 3;
$ctype = isset($settings['ogads_ctype']) ? (int)$settings['ogads_ctype'] : null;

// Get available offers from the API
$offers_result = getOffers(null, null, $ctype, $max_offers, $min_offers);

// For debugging purposes, log the API response
error_log("OGAds API Response: " . print_r($offers_result, true));

// Prepare offers for display
$offers = [];

// Check if we got a valid response
if ($offers_result['status'] === 'success') {
    // API returned a response (might have offers or an error message)
    if (isset($offers_result['offers']['error'])) {
        // API returned an error, but it's still a valid API connection
        $error_message = $offers_result['offers']['error'];
        $message = "No offers available: " . $error_message;
        $message_type = "info";
        
        // Use sample offers for demonstration
        $use_sample_offers = true;
    } elseif (isset($offers_result['offers']) && is_array($offers_result['offers']) && !empty($offers_result['offers'])) {
        // API returned valid offers
        $offers = $offers_result['offers'];
        $use_sample_offers = false;
    } else {
        // API returned success but no offers (empty array or null)
        $message = "No offers available for your region at this time.";
        $message_type = "info";
        $use_sample_offers = true;
    }
} else {
    // API request failed completely
    $error_message = isset($offers_result['message']) ? $offers_result['message'] : "Connection issue";
    
    if (empty($api_key)) {
        $message = "API Key not configured. Please set up your OGAds API Key in the admin panel.";
    } else {
        $message = "API Error: " . $error_message;
    }
    $message_type = "info";
    
    // Use sample offers for this case
    $use_sample_offers = true;
}

// Initialize $offers as an empty array if it's not set
if (!isset($offers)) {
    $offers = [];
}

// If we need to use sample offers, set them up
if ($use_sample_offers) {
    
    // Provide sample offers for testing
    $offers = [
        [
            'id' => 'offer1',
            'name' => 'Install Game App',
            'description' => 'Download and play this exciting new game for 5 minutes.',
            'requirements' => 'Download, install, and open the app. Play for at least 5 minutes.',
            'payout' => 1.5,
            'type' => 'cpi'
        ],
        [
            'id' => 'offer2',
            'name' => 'Complete Short Survey',
            'description' => 'Answer a few questions about your shopping habits.',
            'requirements' => 'Complete the entire survey honestly.',
            'payout' => 2.0,
            'type' => 'cpa'
        ],
        [
            'id' => 'offer3',
            'name' => 'Sign Up for Free Trial',
            'description' => 'Create an account and start a free trial of this service.',
            'requirements' => 'Create a new account with valid information.',
            'payout' => 3.5,
            'type' => 'cpa'
        ]
    ];
}

// Generate unique postback URL for this user
$postback_url = generatePostbackUrl($user_id);

include 'includes/header.php';
?>

<div class="row">
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Available Tasks</h5>
            </div>
            <div class="card-body">
                <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?>"><?php echo $message; ?></div>
                <?php endif; ?>
                
                <?php if (empty($offers)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    No tasks available at the moment. Please check back later.
                </div>
                <?php else: ?>
                
                <div class="row">
                    <?php 
                    // Process and display the offers from OGAds API
                    foreach ($offers as $offer):
                        // Extract relevant information from the offer
                        $offer_id = isset($offer['id']) ? $offer['id'] : '';
                        $offer_name = isset($offer['name']) ? $offer['name'] : 'Unnamed Offer';
                        $offer_description = isset($offer['description']) ? $offer['description'] : '';
                        $offer_requirements = isset($offer['requirements']) ? $offer['requirements'] : 'Complete all offer requirements to earn points.';
                        $offer_payout = isset($offer['payout']) ? (float)$offer['payout'] : 0;
                        
                        // Determine offer type (defaulting to CPA if not specified)
                        $offer_type = isset($offer['type']) ? strtolower($offer['type']) : 'cpa';
                        
                        // Calculate points based on payout
                        $points = (int)($offer_payout * POINTS_CONVERSION_RATE);
                    ?>
                    <div class="col-md-6 mb-4">
                        <div class="card h-100 task-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <h5 class="card-title mb-0"><?php echo isset($offer['name']) ? htmlspecialchars($offer['name']) : 'No title'; ?></h5>
                                    <span class="offer-type offer-type-<?php echo $offer_type; ?>"><?php echo strtoupper($offer_type); ?></span>
                                </div>
                                <p class="card-text"><?php echo isset($offer['description']) ? htmlspecialchars($offer['description']) : 'No description'; ?></p>
                                <div class="d-flex justify-content-between align-items-center mt-3">
                                    <div class="task-payout">
                                        <strong><?php echo formatPoints($points); ?></strong> points
                                    </div>
                                    <button type="button" class="btn btn-sm btn-primary view-task" 
                                        data-id="<?php echo isset($offer['id']) ? $offer['id'] : ''; ?>"
                                        data-title="<?php echo isset($offer['name']) ? htmlspecialchars($offer['name']) : 'No title'; ?>"
                                        data-description="<?php echo isset($offer['description']) ? htmlspecialchars($offer['description']) : 'No description'; ?>"
                                        data-requirements="<?php echo isset($offer['requirements']) ? htmlspecialchars($offer['requirements']) : 'No requirements'; ?>"
                                        data-payout="<?php echo formatCurrency($offer_payout); ?>"
                                        data-points="<?php echo formatPoints($points); ?>">
                                        View Details
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Your Postback URL</h5>
            </div>
            <div class="card-body">
                <p class="small text-muted mb-2">Use this URL to track your offer completions:</p>
                <div class="input-group mb-3">
                    <input type="text" class="form-control form-control-sm" value="<?php echo $postback_url; ?>" readonly>
                    <button class="btn btn-outline-secondary copy-btn" type="button" data-copy="<?php echo $postback_url; ?>">
                        <i class="fas fa-copy"></i>
                    </button>
                </div>
                <div class="alert alert-info small">
                    <i class="fas fa-info-circle me-2"></i>
                    This URL is for offer tracking. You'll need to provide it to OGAds to track your completed offers.
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Tips for Completing Tasks</h5>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item">
                        <i class="fas fa-check-circle text-success me-2"></i>
                        Complete all requirements fully
                    </li>
                    <li class="list-group-item">
                        <i class="fas fa-check-circle text-success me-2"></i>
                        Use authentic information
                    </li>
                    <li class="list-group-item">
                        <i class="fas fa-check-circle text-success me-2"></i>
                        Disable ad blockers during tasks
                    </li>
                    <li class="list-group-item">
                        <i class="fas fa-check-circle text-success me-2"></i>
                        Allow notifications if required
                    </li>
                    <li class="list-group-item">
                        <i class="fas fa-check-circle text-success me-2"></i>
                        Points are credited after verification
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Task Details Modal -->
<div class="modal fade" id="taskModal" tabindex="-1" aria-labelledby="taskModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="taskModalLabel">Task Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h6>Description</h6>
                <p id="taskDescription"></p>
                
                <h6>Requirements</h6>
                <p id="taskRequirements"></p>
                
                <div class="d-flex justify-content-between mb-3">
                    <div>
                        <h6>Payout</h6>
                        <p id="taskPayout" class="mb-0"></p>
                    </div>
                    <div>
                        <h6>Points</h6>
                        <p id="taskPoints" class="mb-0"></p>
                    </div>
                </div>
                
                <form id="taskForm" method="get" action="">
                    <input type="hidden" name="action" value="start">
                    <input type="hidden" name="offer_id" value="">
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary start-task-btn">Start Task</button>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
