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
            // Redirect to OGAds offer URL
            // In a real implementation, we would use the offer URL from the API
            // For simplicity, we'll just show a message
            $message = 'Task initiated! Complete the requirements to earn points.';
            $message_type = 'success';
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

// Get available offers from the API
$offers_result = getOffers();

// Prepare offers for display
$offers = [];
if ($offers_result['status'] === 'success' && isset($offers_result['offers'])) {
    $offers = $offers_result['offers'];
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
                    // For demonstration, we'll create some sample offers
                    // In a real implementation, these would come from the API
                    $sample_offers = [
                        [
                            'id' => 'offer1',
                            'name' => 'Install Game App',
                            'description' => 'Download and play this exciting new game for 5 minutes.',
                            'requirements' => 'Download, install, and open the app. Play for at least 5 minutes.',
                            'payout' => 1.5,
                            'countries' => ['US', 'UK', 'CA', 'AU'],
                            'devices' => ['android', 'ios'],
                            'type' => 'cpi'
                        ],
                        [
                            'id' => 'offer2',
                            'name' => 'Complete Short Survey',
                            'description' => 'Answer a few questions about your shopping habits.',
                            'requirements' => 'Complete the entire survey honestly.',
                            'payout' => 2.0,
                            'countries' => ['US', 'UK', 'CA', 'DE', 'FR'],
                            'devices' => ['all'],
                            'type' => 'cpa'
                        ],
                        [
                            'id' => 'offer3',
                            'name' => 'Sign Up for Free Trial',
                            'description' => 'Create an account and start a free trial of this service.',
                            'requirements' => 'Create a new account with valid information. Credit card required but will not be charged.',
                            'payout' => 3.5,
                            'countries' => ['US', 'CA'],
                            'devices' => ['all'],
                            'type' => 'cpa'
                        ],
                        [
                            'id' => 'offer4',
                            'name' => 'Watch Video Tutorials',
                            'description' => 'Watch a series of short educational videos.',
                            'requirements' => 'Watch all videos in the series completely.',
                            'payout' => 1.0,
                            'countries' => ['all'],
                            'devices' => ['all'],
                            'type' => 'vid'
                        ],
                        [
                            'id' => 'offer5',
                            'name' => 'Mobile Game Level 5',
                            'description' => 'Download this mobile game and reach level 5.',
                            'requirements' => 'Install the game and play until you reach level 5.',
                            'payout' => 2.5,
                            'countries' => ['US', 'UK', 'CA', 'AU', 'DE', 'FR'],
                            'devices' => ['android', 'ios'],
                            'type' => 'cpi'
                        ],
                        [
                            'id' => 'offer6',
                            'name' => 'Complete Registration',
                            'description' => 'Create an account on this platform.',
                            'requirements' => 'Create a new account with valid information.',
                            'payout' => 1.2,
                            'countries' => ['all'],
                            'devices' => ['all'],
                            'type' => 'cpa'
                        ]
                    ];
                    
                    foreach ($sample_offers as $offer):
                        $points = (int)($offer['payout'] * POINTS_CONVERSION_RATE);
                    ?>
                    <div class="col-md-6 mb-4">
                        <div class="card h-100 task-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <h5 class="card-title mb-0"><?php echo htmlspecialchars($offer['name']); ?></h5>
                                    <span class="offer-type offer-type-<?php echo $offer['type']; ?>"><?php echo strtoupper($offer['type']); ?></span>
                                </div>
                                <p class="card-text"><?php echo htmlspecialchars($offer['description']); ?></p>
                                <div class="d-flex justify-content-between align-items-center mt-3">
                                    <div class="task-payout">
                                        <strong><?php echo formatPoints($points); ?></strong> points
                                    </div>
                                    <button type="button" class="btn btn-sm btn-primary view-task" 
                                        data-id="<?php echo $offer['id']; ?>"
                                        data-title="<?php echo htmlspecialchars($offer['name']); ?>"
                                        data-description="<?php echo htmlspecialchars($offer['description']); ?>"
                                        data-requirements="<?php echo htmlspecialchars($offer['requirements']); ?>"
                                        data-payout="<?php echo formatCurrency($offer['payout']); ?>"
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
