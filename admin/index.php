<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../includes/db.php';

$auth = new Auth();

// Check if user is logged in and is admin
if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
    header('Location: ../login.php');
    exit;
}

$db = Database::getInstance();
$conn = $db->getConnection();

// Get overview statistics
try {
    // Total users
    $query = "SELECT COUNT(*) as total FROM users";
    $result = $conn->query($query);
    $row = $result->fetch(PDO::FETCH_ASSOC);
    $total_users = $row['total'];

    // Total points in circulation
    $query = "SELECT SUM(points) as total FROM users";
    $result = $conn->query($query);
    $row = $result->fetch(PDO::FETCH_ASSOC);
    $total_points = $row['total'] ?: 0;

    // Total completed offers
    $query = "SELECT COUNT(*) as total FROM user_offers WHERE completed = 1";
    $result = $conn->query($query);
    $row = $result->fetch(PDO::FETCH_ASSOC);
    $total_offers = $row['total'];

    // Total redemptions
    $query = "SELECT COUNT(*) as total FROM redemptions";
    $result = $conn->query($query);
    $row = $result->fetch(PDO::FETCH_ASSOC);
    $total_redemptions = $row['total'];

    // Pending redemptions
    $query = "SELECT COUNT(*) as total FROM redemptions WHERE status = 'pending'";
    $result = $conn->query($query);
    $row = $result->fetch(PDO::FETCH_ASSOC);
    $pending_redemptions = $row['total'];
} catch (PDOException $e) {
    error_log("Error fetching admin dashboard statistics: " . $e->getMessage());
    // Set defaults in case of error
    $total_users = 0;
    $total_points = 0;
    $total_offers = 0;
    $total_redemptions = 0;
    $pending_redemptions = 0;
}

// Recent transactions
$query = "SELECT t.*, u.username FROM transactions t 
          JOIN users u ON t.user_id = u.id 
          ORDER BY t.created_at DESC LIMIT 10";
$result = $db->query($query);
$recent_transactions = [];
while ($row = $result->fetch_assoc()) {
    $recent_transactions[] = $row;
}

// Recent redemptions
$query = "SELECT r.*, u.username, rw.name as reward_name FROM redemptions r 
          JOIN users u ON r.user_id = u.id 
          JOIN rewards rw ON r.reward_id = rw.id 
          ORDER BY r.created_at DESC LIMIT 10";
$result = $db->query($query);
$recent_redemptions = [];
while ($row = $result->fetch_assoc()) {
    $recent_redemptions[] = $row;
}

// Daily activity (last 7 days)
$query = "SELECT COUNT(*) as count, DATE(created_at) as date 
          FROM transactions 
          WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) 
          GROUP BY DATE(created_at) 
          ORDER BY date ASC";
$result = $db->query($query);
$daily_activity = [];
while ($row = $result->fetch_assoc()) {
    $daily_activity[$row['date']] = $row['count'];
}

// New users (last 7 days)
$query = "SELECT COUNT(*) as count, DATE(created_at) as date 
          FROM users 
          WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) 
          GROUP BY DATE(created_at) 
          ORDER BY date ASC";
$result = $db->query($query);
$new_users = [];
while ($row = $result->fetch_assoc()) {
    $new_users[$row['date']] = $row['count'];
}

include 'header.php';
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Admin Dashboard</h1>
    </div>

    <!-- Overview Statistics -->
    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Users</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_users; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Points in Circulation</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo formatPoints($total_points); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-coins fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Completed Offers</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_offers; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-tasks fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Pending Redemptions</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $pending_redemptions; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-gift fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Content Row -->
    <div class="row">
        <!-- Recent Transactions -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Recent Transactions</h6>
                    <a href="transactions.php" class="btn btn-sm btn-primary">View All</a>
                </div>
                <div class="card-body">
                    <?php if (count($recent_transactions) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Type</th>
                                    <th>Points</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_transactions as $transaction): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($transaction['username']); ?></td>
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
                    <div class="text-center py-4">
                        <p class="text-muted">No transactions yet</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Recent Redemptions -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Recent Redemptions</h6>
                    <a href="redemptions.php" class="btn btn-sm btn-primary">View All</a>
                </div>
                <div class="card-body">
                    <?php if (count($recent_redemptions) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Reward</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_redemptions as $redemption): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($redemption['username']); ?></td>
                                    <td><?php echo htmlspecialchars($redemption['reward_name']); ?></td>
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
                    <div class="text-center py-4">
                        <p class="text-muted">No redemptions yet</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Access Buttons -->
    <div class="row mb-4">
        <div class="col-lg-12">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Quick Actions</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <a href="users.php" class="btn btn-primary btn-icon-split btn-lg w-100">
                                <span class="icon text-white-50">
                                    <i class="fas fa-users"></i>
                                </span>
                                <span class="text">Manage Users</span>
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="rewards.php" class="btn btn-success btn-icon-split btn-lg w-100">
                                <span class="icon text-white-50">
                                    <i class="fas fa-gift"></i>
                                </span>
                                <span class="text">Manage Rewards</span>
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="redemptions.php" class="btn btn-warning btn-icon-split btn-lg w-100">
                                <span class="icon text-white-50">
                                    <i class="fas fa-exchange-alt"></i>
                                </span>
                                <span class="text">Manage Redemptions</span>
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="transactions.php" class="btn btn-info btn-icon-split btn-lg w-100">
                                <span class="icon text-white-50">
                                    <i class="fas fa-money-bill-wave"></i>
                                </span>
                                <span class="text">View Transactions</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- System Information -->
    <div class="row">
        <div class="col-lg-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">System Information</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-4">
                                <h5>Application</h5>
                                <p><strong>Name:</strong> <?php echo APP_NAME; ?></p>
                                <p><strong>URL:</strong> <?php echo APP_URL; ?></p>
                                <p><strong>Points Conversion:</strong> <?php echo POINTS_CONVERSION_RATE; ?> points = $1.00</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-4">
                                <h5>Database</h5>
                                <p><strong>Host:</strong> <?php echo DB_HOST; ?></p>
                                <p><strong>Database:</strong> <?php echo DB_NAME; ?></p>
                                <p><strong>Total Tables:</strong> 7</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-4">
                                <h5>PHP Information</h5>
                                <p><strong>Version:</strong> <?php echo phpversion(); ?></p>
                                <p><strong>Server:</strong> <?php echo $_SERVER['SERVER_SOFTWARE']; ?></p>
                                <p><strong>Timezone:</strong> <?php echo date_default_timezone_get(); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<?php include 'footer.php'; ?>
