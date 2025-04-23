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

// Get user stats
$stats = getUserStats($user_id);

// Get recent transactions
$transactions = getUserTransactions($user_id, 5);

// Get user redemptions
$redemptions = getUserRedemptions($user_id);

// Determine active tab
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'overview';

include 'includes/header.php';
?>

<div class="dashboard-container">
    <!-- Dashboard Sidebar -->
    <div class="dashboard-sidebar">
        <?php include 'includes/sidebar.php'; ?>
    </div>
    
    <!-- Dashboard Content -->
    <div class="dashboard-content">
        <div class="card">
            <div class="card-header bg-white">
                <ul class="nav nav-tabs card-header-tabs" id="dashboardTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <a class="nav-link <?php echo $tab === 'overview' ? 'active' : ''; ?>" id="overview-tab" data-bs-toggle="tab" href="#overview" role="tab" aria-controls="overview" aria-selected="<?php echo $tab === 'overview' ? 'true' : 'false'; ?>">Overview</a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link <?php echo $tab === 'transactions' ? 'active' : ''; ?>" id="transactions-tab" data-bs-toggle="tab" href="#transactions" role="tab" aria-controls="transactions" aria-selected="<?php echo $tab === 'transactions' ? 'true' : 'false'; ?>">Transactions</a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link <?php echo $tab === 'redemptions' ? 'active' : ''; ?>" id="redemptions-tab" data-bs-toggle="tab" href="#redemptions" role="tab" aria-controls="redemptions" aria-selected="<?php echo $tab === 'redemptions' ? 'true' : 'false'; ?>">Redemptions</a>
                    </li>
                </ul>
            </div>
            <div class="tab-content" id="dashboardTabContent">
                <!-- Overview Tab -->
                <div class="tab-pane fade <?php echo $tab === 'overview' ? 'show active' : ''; ?>" id="overview" role="tabpanel" aria-labelledby="overview-tab">
                    <h3 class="mb-4">Dashboard Overview</h3>
                    
                    <!-- Stats Cards -->
                    <div class="row mb-4">
                        <div class="col-md-6 col-lg-3 mb-3">
                            <div class="card bg-primary text-white h-100">
                                <div class="card-body">
                                    <h5 class="card-title">Available Points</h5>
                                    <h2 class="mb-0"><?php echo formatPoints($current_user['points']); ?></h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3 mb-3">
                            <div class="card bg-success text-white h-100">
                                <div class="card-body">
                                    <h5 class="card-title">Total Earned</h5>
                                    <h2 class="mb-0"><?php echo formatPoints($stats['total_earned']); ?></h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3 mb-3">
                            <div class="card bg-info text-white h-100">
                                <div class="card-body">
                                    <h5 class="card-title">Completed Offers</h5>
                                    <h2 class="mb-0"><?php echo $stats['completed_offers']; ?></h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3 mb-3">
                            <div class="card bg-warning text-white h-100">
                                <div class="card-body">
                                    <h5 class="card-title">Redeemed Rewards</h5>
                                    <h2 class="mb-0"><?php echo $stats['redeemed_rewards']; ?></h2>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Quick Actions -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">Quick Actions</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <a href="tasks.php" class="btn btn-primary d-block">
                                                <i class="fas fa-tasks me-2"></i> Find Tasks
                                            </a>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <a href="rewards.php" class="btn btn-success d-block">
                                                <i class="fas fa-gift me-2"></i> View Rewards
                                            </a>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <a href="#" class="btn btn-info d-block">
                                                <i class="fas fa-user me-2"></i> Edit Profile
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Recent Activity -->
                    <div class="row">
                        <div class="col-lg-6 mb-4">
                            <div class="card h-100">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">Recent Transactions</h5>
                                    <a href="#transactions" data-bs-toggle="tab" class="btn btn-sm btn-outline-primary">View All</a>
                                </div>
                                <div class="card-body">
                                    <?php if (count($transactions) > 0): ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Type</th>
                                                    <th>Points</th>
                                                    <th>Date</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($transactions as $transaction): ?>
                                                <tr>
                                                    <td>
                                                        <?php if ($transaction['type'] === 'earn'): ?>
                                                            <span class="badge bg-success">Earned</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-secondary">Spent</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo formatPoints($transaction['points']); ?></td>
                                                    <td><?php echo formatDate($transaction['created_at']); ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <?php else: ?>
                                    <p class="text-center text-muted my-4">No transactions yet</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-6 mb-4">
                            <div class="card h-100">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">Recent Redemptions</h5>
                                    <a href="#redemptions" data-bs-toggle="tab" class="btn btn-sm btn-outline-primary">View All</a>
                                </div>
                                <div class="card-body">
                                    <?php 
                                    $recent_redemptions = array_slice($redemptions, 0, 5);
                                    if (count($recent_redemptions) > 0): 
                                    ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Reward</th>
                                                    <th>Status</th>
                                                    <th>Date</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($recent_redemptions as $redemption): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($redemption['name']); ?></td>
                                                    <td>
                                                        <?php if ($redemption['status'] === 'pending'): ?>
                                                            <span class="badge bg-warning">Pending</span>
                                                        <?php elseif ($redemption['status'] === 'completed'): ?>
                                                            <span class="badge bg-success">Completed</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-danger">Rejected</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo formatDate($redemption['created_at']); ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <?php else: ?>
                                    <p class="text-center text-muted my-4">No redemptions yet</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Transactions Tab -->
                <div class="tab-pane fade <?php echo $tab === 'transactions' ? 'show active' : ''; ?>" id="transactions" role="tabpanel" aria-labelledby="transactions-tab">
                    <h3 class="mb-4">Transaction History</h3>
                    
                    <?php 
                    // Get all transactions for the user
                    $all_transactions = getUserTransactions($user_id, 100);
                    if (count($all_transactions) > 0): 
                    ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Description</th>
                                    <th>Type</th>
                                    <th>Points</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($all_transactions as $transaction): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($transaction['description']); ?></td>
                                    <td>
                                        <?php if ($transaction['type'] === 'earn'): ?>
                                            <span class="badge bg-success">Earned</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Spent</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo formatPoints($transaction['points']); ?></td>
                                    <td><?php echo formatDate($transaction['created_at']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-info text-center">
                        <i class="fas fa-info-circle me-2"></i>
                        No transactions yet. Start completing tasks to earn points!
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Redemptions Tab -->
                <div class="tab-pane fade <?php echo $tab === 'redemptions' ? 'show active' : ''; ?>" id="redemptions" role="tabpanel" aria-labelledby="redemptions-tab">
                    <h3 class="mb-4">Redemption History</h3>
                    
                    <?php if (count($redemptions) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Reward</th>
                                    <th>Points Used</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($redemptions as $redemption): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($redemption['name']); ?></td>
                                    <td><?php echo formatPoints($redemption['points_used']); ?></td>
                                    <td>
                                        <?php if ($redemption['status'] === 'pending'): ?>
                                            <span class="badge bg-warning">Pending</span>
                                        <?php elseif ($redemption['status'] === 'completed'): ?>
                                            <span class="badge bg-success">Completed</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Rejected</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo formatDate($redemption['created_at']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-info text-center">
                        <i class="fas fa-info-circle me-2"></i>
                        No redemptions yet. Redeem your points for rewards!
                    </div>
                    <div class="text-center mt-4">
                        <a href="rewards.php" class="btn btn-primary">
                            <i class="fas fa-gift me-2"></i> Browse Rewards
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
