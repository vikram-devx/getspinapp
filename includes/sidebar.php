<?php
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$auth = new Auth();
$current_user = $auth->getUser();
$stats = getUserStats($current_user['id']);
?>

<div class="card mb-4 d-none d-md-block">
    <div class="card-header">
        <h5 class="mb-0">User Profile</h5>
    </div>
    <div class="card-body">
        <div class="text-center mb-3">
            <div class="avatar-placeholder bg-primary text-white rounded-circle mx-auto mb-2" style="width: 70px; height: 70px; font-size: 1.5rem;">
                <?php echo strtoupper(substr($current_user['username'], 0, 1)); ?>
            </div>
            <div>
                <h6 class="mb-1"><?php echo htmlspecialchars($current_user['username']); ?></h6>
                <small class="text-muted">Member since <?php echo date('M Y', strtotime($current_user['created_at'])); ?></small>
            </div>
        </div>
        <div class="points-display text-center mb-3">
            <h3 class="mb-0"><?php echo formatPoints($current_user['points']); ?></h3>
            <small class="text-muted">Available Points</small>
        </div>
        <div class="d-grid gap-2">
            <a href="<?php echo url('rewards'); ?>" class="btn btn-primary"><i class="fas fa-gift me-2"></i>Redeem Points</a>
            <a href="<?php echo url('tasks'); ?>" class="btn btn-outline-primary"><i class="fas fa-tasks me-2"></i>Available Tasks <span class="badge bg-warning text-dark ms-1" style="font-size: 0.7rem;">Earn Free Spins</span></a>
            <a href="<?php echo url('profile'); ?>" class="btn btn-outline-secondary"><i class="fas fa-user-cog me-2"></i>Edit Profile</a>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">Stats</h5>
    </div>
    <div class="card-body">
        <ul class="list-group list-group-flush">
            <li class="list-group-item d-flex justify-content-between align-items-center">
                Total Earned
                <span class="badge bg-primary rounded-pill"><?php echo formatPoints($stats['total_earned']); ?></span>
            </li>
            <li class="list-group-item d-flex justify-content-between align-items-center">
                Total Spent
                <span class="badge bg-secondary rounded-pill"><?php echo formatPoints($stats['total_spent']); ?></span>
            </li>
            <li class="list-group-item d-flex justify-content-between align-items-center">
                Completed Offers
                <span class="badge bg-success rounded-pill"><?php echo $stats['completed_offers']; ?></span>
            </li>
            <li class="list-group-item d-flex justify-content-between align-items-center">
                Redeemed Rewards
                <span class="badge bg-info rounded-pill"><?php echo $stats['redeemed_rewards']; ?></span>
            </li>
            <li class="list-group-item d-flex justify-content-between align-items-center bg-light">
                Your Referrals
                <span class="badge bg-warning rounded-pill"><?php echo $stats['total_referrals']; ?></span>
            </li>
            <li class="list-group-item d-flex justify-content-between align-items-center bg-light">
                Referral Income
                <span class="badge bg-warning rounded-pill"><?php echo formatPoints($stats['referral_income']); ?></span>
            </li>
        </ul>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Quick Links</h5>
    </div>
    <div class="card-body">
        <ul class="list-group list-group-flush">
            <li class="list-group-item">
                <a href="<?php echo url('tasks'); ?>" class="text-decoration-none">
                    <i class="fas fa-tasks me-2"></i> Available Tasks <span class="badge bg-warning text-dark" style="font-size: 0.7rem;">Earn Free Spins</span>
                </a>
            </li>
            <li class="list-group-item">
                <a href="<?php echo url('rewards'); ?>" class="text-decoration-none">
                    <i class="fas fa-gift me-2"></i> Rewards Catalog
                </a>
            </li>
            <li class="list-group-item">
                <a href="<?php echo url('redemption_history'); ?>" class="text-decoration-none">
                    <i class="fas fa-history me-2"></i> Redemption History
                </a>
            </li>
            <li class="list-group-item">
                <a href="<?php echo url('transactions'); ?>" class="text-decoration-none">
                    <i class="fas fa-exchange-alt me-2"></i> Transaction History
                </a>
            </li>
            <li class="list-group-item">
                <a href="<?php echo url('leaderboard'); ?>" class="text-decoration-none">
                    <i class="fas fa-trophy me-2"></i> User Leaderboard
                </a>
            </li>
            <li class="list-group-item">
                <a href="<?php echo url('profile'); ?>" class="text-decoration-none">
                    <i class="fas fa-user-cog me-2"></i> Edit Profile
                </a>
            </li>
            <li class="list-group-item bg-light">
                <a href="<?php echo url('referrals'); ?>" class="text-decoration-none">
                    <i class="fas fa-user-plus me-2"></i> <strong>Refer Friends & Earn 100 Points!</strong>
                </a>
            </li>
        </ul>
    </div>
</div>
