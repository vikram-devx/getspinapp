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
        
        // For debugging
        error_log("Starting task for offer ID: " . $offer_id);
        
        // Record the offer attempt
        $result = recordOfferAttempt($user_id, $offer_id, $ip_address);
        
        if ($result['status'] === 'success') {
            // Initialize task progress tracking
            trackTaskProgress(
                $user_id, 
                $offer_id, 
                'started', 
                5, 
                'Task started - waiting for offer interaction', 
                300 // 5 minutes estimated completion time
            );
            
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
                $offer_link = '';
                
                // First check if the offer link was passed directly via the form
                if (isset($_GET['offer_link']) && !empty($_GET['offer_link'])) {
                    $offer_link = $_GET['offer_link']; 
                    error_log("Using offer link from form: " . $offer_link);
                } else if (isset($_POST['offer_link']) && !empty($_POST['offer_link'])) {
                    $offer_link = $_POST['offer_link'];
                    error_log("Using offer link from POST form: " . $offer_link);
                } else {
                    // Try to find the offer in our current offers array
                    foreach ($offers as $offer) {
                        if (isset($offer['offerid']) && $offer['offerid'] == $offer_id && isset($offer['link'])) {
                            $offer_link = $offer['link'];
                            error_log("Found offer link in offers array: " . $offer_link);
                            break;
                        }
                    }
                    
                    // If we couldn't find the offer in our cache, try to get it from the API
                    if (empty($offer_link)) {
                        $offer_details = getOfferDetails($offer_id);
                        
                        // Check for 'link' field which is the correct field name in OGAds API
                        if ($offer_details['status'] === 'success' && isset($offer_details['offer']) && 
                            (isset($offer_details['offer']['link']) || isset($offer_details['offer']['tracking_url']))) {
                            
                            // Use whichever field is available
                            if (isset($offer_details['offer']['link'])) {
                                $offer_link = $offer_details['offer']['link'];
                                error_log("Got offer link from API (link field): " . $offer_link);
                            } else {
                                $offer_link = $offer_details['offer']['tracking_url'];
                                error_log("Got offer link from API (tracking_url field): " . $offer_link);
                            }
                        }
                    }
                }
                
                if (!empty($offer_link)) {
                    // Append our postback parameters to the tracking URL
                    $tracking_url = $offer_link;
                    
                    // Add affiliate sub parameter for tracking which user completed the offer
                    // This will be passed back via the postback URL from OGAds
                    // We use aff_sub4 as recommended in OGAds documentation
                    if (strpos($tracking_url, 'aff_sub4=') === false) {
                        $tracking_url .= (strpos($tracking_url, '?') !== false ? '&' : '?') . 'aff_sub4=' . $user_id;
                    }
                    
                    // Also include as aff_sub in case the API uses that parameter instead
                    if (strpos($tracking_url, 'aff_sub=') === false) {
                        $tracking_url .= '&aff_sub=' . $user_id;
                    }
                    
                    error_log("Redirecting to tracking URL: " . $tracking_url);
                    
                    // Only redirect if we're not handling an AJAX request or hidden form submission
                    if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) && empty($_POST['hidden_form_submission']) && empty($_GET['hidden_form_submission'])) {
                        // Redirect the user to the offer URL
                        header('Location: ' . $tracking_url);
                        exit;
                    } else {
                        // Just return success for hidden form submissions
                        echo json_encode(['success' => true, 'message' => 'Offer tracking recorded']);
                        exit;
                    }
                } else {
                    // Fallback if we couldn't get the tracking URL
                    $message = 'Could not start task. Please try again later.';
                    $message_type = 'warning';
                    error_log("No offer link found for offer ID: " . $offer_id);
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
    // Based on the documentation, we need to check for:
    // { "success": true, "error": null, "offers": [ ... array of offers ... ] }
    
    // Debug log the full response structure
    error_log("Full API Response Structure in tasks.php: " . print_r($offers_result, true));
    
    if (isset($offers_result['offers']['error']) && !empty($offers_result['offers']['error'])) {
        // API returned an error
        $error_message = $offers_result['offers']['error'];
        $message = "No offers available: " . $error_message;
        $message_type = "info";
        
        // Use sample offers for demonstration
        $use_sample_offers = true;
    } elseif (isset($offers_result['offers']['success']) && $offers_result['offers']['success'] === true && 
              isset($offers_result['offers']['offers']) && is_array($offers_result['offers']['offers']) && 
              !empty($offers_result['offers']['offers'])) {
        // API returned valid offers as per documentation { "success": true, "error": null, "offers": [ ... array of offers ... ] }
        $offers = $offers_result['offers']['offers'];
        $use_sample_offers = false;
        
        // For debugging, log the first offer to see its structure
        if (isset($offers[0])) {
            error_log("First OGAds Offer Structure: " . print_r($offers[0], true));
        }
    } else {
        // Check if we have a direct array of offers (without the success/error wrapper)
        if (isset($offers_result['offers']) && is_array($offers_result['offers']) && 
            !isset($offers_result['offers']['success']) && !isset($offers_result['offers']['error'])) {
            
            $offers = $offers_result['offers'];
            $use_sample_offers = false;
            
            // For debugging, log the first offer
            if (isset($offers[0])) {
                error_log("First OGAds Direct Offer Structure: " . print_r($offers[0], true));
            }
        } else {
            // API returned success but no offers (empty array or null)
            $message = "No offers available for your region at this time.";
            $message_type = "info";
            $use_sample_offers = true;
        }
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

// Postback URL generation removed as per requirements

include 'includes/header.php';
?>

<div class="row">
    <div class="col-lg-8">
        <!-- Task Progress Section -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Your Active Tasks</h5>
            </div>
            <div class="card-body">
                <div id="task-progress-container">
                    <p class="text-center text-muted" id="no-tasks-message">You don't have any active tasks.</p>
                    <!-- Task progress items will be added here dynamically -->
                </div>
            </div>
        </div>
        
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
                        // Extract relevant information from the offer based on OGAds API structure
                        $offer_id = isset($offer['offerid']) ? $offer['offerid'] : '';
                        $offer_name = isset($offer['name']) ? $offer['name'] : '';
                        if (empty($offer_name) && isset($offer['name_short'])) {
                            $offer_name = $offer['name_short'];
                        }
                        $offer_description = isset($offer['description']) ? $offer['description'] : '';
                        if (empty($offer_description) && isset($offer['adcopy'])) {
                            $offer_description = $offer['adcopy'];
                        }
                        $offer_requirements = 'Complete all offer requirements to earn points.';
                        $offer_payout = isset($offer['payout']) ? (float)$offer['payout'] : 0;
                        
                        // Determine offer type (defaulting to CPA if not specified)
                        $offer_type = isset($offer['ctype']) ? strtolower($offer['ctype']) : 'cpa';
                        
                        // Calculate points based on payout
                        $points = (int)($offer_payout * POINTS_CONVERSION_RATE);
                    ?>
                    <div class="col-md-6 mb-4">
                        <div class="card h-100 task-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <h5 class="card-title mb-0"><?php echo !empty($offer_name) ? htmlspecialchars($offer_name) : 'No title'; ?></h5>
                                    <span class="offer-type offer-type-<?php echo $offer_type; ?>"><?php echo strtoupper($offer_type); ?></span>
                                </div>
                                <p class="card-text"><?php echo !empty($offer_description) ? htmlspecialchars($offer_description) : 'No description'; ?></p>
                                <div class="d-flex justify-content-between align-items-center mt-3">
                                    <div class="task-payout">
                                        <strong><?php echo formatPoints($points); ?></strong> points
                                    </div>
                                    <button type="button" class="btn btn-sm btn-primary view-task" 
                                        data-id="<?php echo $offer_id; ?>"
                                        data-title="<?php echo !empty($offer_name) ? htmlspecialchars($offer_name) : 'No title'; ?>"
                                        data-description="<?php echo !empty($offer_description) ? htmlspecialchars($offer_description) : 'No description'; ?>"
                                        data-requirements="<?php echo htmlspecialchars($offer_requirements); ?>"
                                        data-payout="$<?php echo number_format($offer_payout, 2); ?>"
                                        data-points="<?php echo formatPoints($points); ?>"
                                        data-link="<?php echo isset($offer['link']) ? htmlspecialchars($offer['link']) : ''; ?>">
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
                
                <div class="text-center mb-3">
                    <h6>Points Reward</h6>
                    <p id="taskPoints" class="mb-0 fw-bold"></p>
                </div>
                
                <div class="d-grid">
                    <a href="#" id="directTaskLink" class="btn btn-primary start-task-btn" target="_blank">Start Task</a>
                </div>
                
                <!-- Hidden form that records the task start in our database -->
                <form id="taskForm" method="get" action="tasks.php" style="display:none;">
                    <input type="hidden" name="action" value="start">
                    <input type="hidden" name="offer_id" value="">
                    <input type="hidden" name="offer_link" value="">
                    <input type="hidden" name="hidden_form_submission" value="1">
                    <button type="submit" id="hiddenSubmitButton">Submit</button>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
