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

// Get user stats
$stats = getUserStats($user_id);

// Get recent transactions
$transactions = getUserTransactions($user_id, 5);

// Get user redemptions
$redemptions = getUserRedemptions($user_id);

// Get top users for mini leaderboard (top 5)
$db = Database::getInstance();
$conn = $db->getConnection();
$leaderboard_query = "SELECT id, username, points, 
    (SELECT COUNT(*) FROM users u2 WHERE u2.points > u1.points) + 1 AS rank 
    FROM users u1 
    WHERE is_admin = 0 
    ORDER BY points DESC 
    LIMIT 5";
$leaderboard_result = $conn->query($leaderboard_query);
$top_users = [];
if ($leaderboard_result) {
    while ($row = $leaderboard_result->fetch(PDO::FETCH_ASSOC)) {
        $top_users[] = $row;
    }
}

// Get current user's rank
$rank_query = "SELECT (SELECT COUNT(*) FROM users u2 WHERE u2.points > u1.points) + 1 AS rank 
              FROM users u1 WHERE id = :user_id";
$stmt = $conn->prepare($rank_query);
$stmt->execute(['user_id' => $user_id]);
$user_rank = $stmt->fetch(PDO::FETCH_ASSOC);

// Determine active tab
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'overview';

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
                                            <a href="<?php echo url('tasks'); ?>" class="btn btn-primary d-block">
                                                <i class="fas fa-tasks me-2"></i> Find Tasks
                                            </a>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <a href="<?php echo url('rewards'); ?>" class="btn btn-success d-block">
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
                        <div class="col-lg-4 mb-4">
                            <div class="card h-100">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">Leaderboard</h5>
                                    <a href="<?php echo url('leaderboard'); ?>" class="btn btn-sm btn-outline-primary">Full Leaderboard</a>
                                </div>
                                <div class="card-body">
                                    <?php if (count($top_users) > 0): ?>
                                    <div class="user-rank-highlight mb-3">
                                        <div class="alert alert-primary mb-0 d-flex justify-content-between align-items-center">
                                            <span>Your Rank</span>
                                            <span class="badge bg-primary">#<?php echo $user_rank['rank']; ?></span>
                                        </div>
                                    </div>
                                    <div class="top-users-list">
                                        <?php foreach ($top_users as $index => $user): ?>
                                            <div class="leaderboard-item d-flex justify-content-between align-items-center py-2 <?php echo ($user['id'] == $current_user['id']) ? 'bg-light' : ''; ?>" style="border-bottom: 1px solid #eee;">
                                                <div class="d-flex align-items-center">
                                                    <div class="rank-number me-2">
                                                        <?php if ($user['rank'] <= 3): ?>
                                                            <?php if ($user['rank'] == 1): ?>
                                                                <i class="fas fa-trophy text-warning"></i>
                                                            <?php elseif ($user['rank'] == 2): ?>
                                                                <i class="fas fa-medal" style="color: #C0C0C0;"></i>
                                                            <?php else: ?>
                                                                <i class="fas fa-medal" style="color: #CD7F32;"></i>
                                                            <?php endif; ?>
                                                        <?php else: ?>
                                                            #<?php echo $user['rank']; ?>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="user-avatar-mini me-2 bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 30px; height: 30px; font-size: 12px;">
                                                        <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                                                    </div>
                                                    <div class="username">
                                                        <?php echo htmlspecialchars($user['username']); ?>
                                                        <?php if ($user['id'] == $current_user['id']): ?>
                                                            <span class="badge bg-secondary ms-1">You</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <div class="points">
                                                    <strong><?php echo formatPoints($user['points']); ?></strong>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php else: ?>
                                    <p class="text-center text-muted my-4">No users on leaderboard yet</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-4 mb-4">
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
                        
                        <div class="col-lg-4 mb-4">
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
                                                    <td>
                                                        <?php echo htmlspecialchars($redemption['name']); ?>
                                                        <?php 
                                                        // Check if this is a game reward with redemption details
                                                        if (($redemption['reward_id'] == 6 || $redemption['reward_id'] == 7) && !empty($redemption['redemption_details'])):
                                                            $details = json_decode($redemption['redemption_details'], true);
                                                            if ($details && isset($details['username'])):
                                                        ?>
                                                        <br>
                                                        <small class="text-muted">
                                                            Username: <strong><?php echo htmlspecialchars($details['username']); ?></strong>
                                                        </small>
                                                        <?php endif; endif; ?>
                                                    </td>
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
                                    <td>
                                        <?php echo htmlspecialchars($redemption['name']); ?>
                                        <?php 
                                        // Check if this is a game reward and has redemption details
                                        if (($redemption['reward_id'] == 6 || $redemption['reward_id'] == 7) && !empty($redemption['redemption_details'])):
                                            $details = json_decode($redemption['redemption_details'], true);
                                            if ($details && isset($details['username']) && isset($details['spins'])):
                                        ?>
                                        <br>
                                        <small class="text-muted">
                                            Game Username: <strong><?php echo htmlspecialchars($details['username']); ?></strong><br>
                                            Spins: <strong><?php echo htmlspecialchars($details['spins']); ?></strong>
                                        </small>
                                        <?php endif; endif; ?>
                                    </td>
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
                        <a href="<?php echo url('rewards'); ?>" class="btn btn-primary">
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
