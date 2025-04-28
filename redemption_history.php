<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$auth = new Auth();

// Check if user is logged in
if (!$auth->isLoggedIn()) {
    header('Location: ' . url('login'));
    exit;
}

$current_user = $auth->getUser();
$user_id = $current_user['id'];

// Get user redemptions - only for the current user
$redemptions = getUserRedemptions($user_id);

include 'includes/header.php';
?>

<div class="dashboard-container">
    <!-- Dashboard Sidebar -->
    <div class="dashboard-sidebar">
        <!-- Mobile-only heading for the sidebar -->
        <div class="mobile-sidebar-heading">
            <i class="fas fa-user-circle me-2"></i>Your Profile
        </div>
        <?php include 'includes/sidebar.php'; ?>
    </div>
    
    <!-- Dashboard Content -->
    <div class="dashboard-content">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center bg-white">
                <h5 class="mb-0">Redemption History</h5>
                <a href="<?php echo url('dashboard'); ?>" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
                </a>
            </div>
            <div class="card-body">
                <?php if (count($redemptions) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Reward</th>
                                <th>Status</th>
                                <th>Points</th>
                                <th>Date</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($redemptions as $redemption): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($redemption['name']); ?></td>
                                <td>
                                    <?php if ($redemption['status'] === 'pending'): ?>
                                        <span class="badge bg-warning text-dark">Pending</span>
                                    <?php elseif ($redemption['status'] === 'completed'): ?>
                                        <span class="badge bg-success">Completed</span>
                                    <?php elseif ($redemption['status'] === 'rejected'): ?>
                                        <span class="badge bg-danger">Rejected</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary"><?php echo ucfirst($redemption['status']); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo formatPoints($redemption['points']); ?></td>
                                <td><?php echo formatDate($redemption['created_at']); ?></td>
                                <td>
                                    <?php if (!empty($redemption['reward_details'])): ?>
                                    <button type="button" class="btn btn-sm btn-outline-info" data-bs-toggle="tooltip" data-bs-placement="top" title="<?php echo htmlspecialchars($redemption['reward_details']); ?>">
                                        <i class="fas fa-info-circle"></i>
                                    </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="alert alert-info">
                    <p class="text-center mb-0">You haven't redeemed any rewards yet. Visit the Rewards page to redeem your points!</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/dashboard_footer.php'; ?>