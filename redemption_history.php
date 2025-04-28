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
    <!-- Dashboard Sidebar - hidden on mobile -->
    <div class="dashboard-sidebar d-none d-md-block">
        <?php include 'includes/sidebar.php'; ?>
    </div>
    
    <!-- Dashboard Content -->
    <div class="dashboard-content w-100">
        <div class="card mx-0">
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
                                <td><?php echo formatPoints($redemption['points_required'] ?? 0); ?></td>
                                <td><?php echo formatDate($redemption['created_at']); ?></td>

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

<style>
    /* Mobile optimization for redemption history */
    @media (max-width: 767px) {
        .dashboard-container {
            padding: 0;
            margin: 0;
            width: 100%;
        }
        
        .dashboard-content {
            padding: 0;
        }
        
        .card {
            border-radius: 0;
            border-left: none;
            border-right: none;
        }
        
        .card-header {
            padding: 0.75rem;
        }
        
        .card-header h5 {
            font-size: 1.1rem;
        }
        
        .card-header .btn {
            font-size: 0.8rem;
            padding: 0.25rem 0.5rem;
        }
        
        .card-body {
            padding: 0.75rem;
        }
        
        .table th {
            font-size: 0.85rem;
            padding: 0.5rem 0.25rem;
        }
        
        .table td {
            font-size: 0.85rem;
            padding: 0.5rem 0.25rem;
        }
        
        .badge {
            font-size: 0.75rem;
            padding: 0.2rem 0.4rem;
        }
        
        .btn-sm {
            padding: 0.2rem 0.4rem;
            font-size: 0.75rem;
        }
    }
</style>

<?php include 'includes/dashboard_footer.php'; ?>