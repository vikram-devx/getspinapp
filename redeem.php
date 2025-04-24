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

$message = '';
$message_type = '';

// Process redemption request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['reward_id'])) {
    $reward_id = (int)$_GET['reward_id'];
    $confirm = isset($_POST['confirm']) ? trim($_POST['confirm']) : '';
    $game_username = isset($_POST['game_username']) ? trim($_POST['game_username']) : '';
    
    // Validate confirmation
    if ($confirm !== 'CONFIRM') {
        $message = 'Please type "CONFIRM" to redeem this reward.';
        $message_type = 'danger';
    } else {
        // Get reward details
        $reward = getRewardById($reward_id);
        
        if (!$reward) {
            $message = 'Invalid reward selected.';
            $message_type = 'danger';
        } else if ($current_user['points'] < $reward['points_required']) {
            $message = 'You do not have enough points to redeem this reward.';
            $message_type = 'danger';
        } 
        // Check if this is a game spin reward that requires a username
        else if (($reward_id == 6 || $reward_id == 7) && empty($game_username)) {
            $message = 'Please enter your game username to receive your spins.';
            $message_type = 'danger';
        } else {
            // Prepare redemption details
            $redemption_details = null;
            if ($reward_id == 6 || $reward_id == 7) {
                $game_type = ($reward_id == 6) ? 'CoinMaster' : 'Monopoly';
                $redemption_details = json_encode([
                    'game_type' => $game_type,
                    'username' => $game_username,
                    'spins' => 100
                ]);
            }
            
            // Process the redemption
            $result = redeemReward($user_id, $reward_id, $redemption_details);
            
            if ($result['status'] === 'success') {
                $message = 'Redemption successful! Your request is now being processed.';
                $message_type = 'success';
            } else {
                $message = $result['message'];
                $message_type = 'danger';
            }
        }
    }
} else {
    // Redirect to rewards page if no reward_id is provided
    header('Location: rewards.php');
    exit;
}

include 'includes/header.php';
?>

<div class="row">
    <div class="col-lg-8 offset-lg-2">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Redemption Status</h5>
            </div>
            <div class="card-body text-center py-5">
                <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?> mb-4">
                    <?php echo $message; ?>
                </div>
                <?php endif; ?>
                
                <?php if ($message_type === 'success'): ?>
                <div class="mb-4">
                    <i class="fas fa-check-circle fa-5x text-success"></i>
                </div>
                <h3 class="mb-3">Thank You for Your Redemption</h3>
                <p class="lead mb-4">Your reward request has been received and is now being processed. You will be notified once it's approved.</p>
                <?php else: ?>
                <div class="mb-4">
                    <i class="fas fa-times-circle fa-5x text-danger"></i>
                </div>
                <h3 class="mb-3">Redemption Failed</h3>
                <p class="lead mb-4">Please try again or contact support if the issue persists.</p>
                <?php endif; ?>
                
                <div class="d-grid gap-2 col-lg-6 mx-auto">
                    <a href="rewards.php" class="btn btn-primary">Back to Rewards</a>
                    <a href="dashboard.php?tab=redemptions" class="btn btn-outline-secondary">View Redemption History</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
