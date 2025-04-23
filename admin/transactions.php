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

// Set up filtering
$filter_user = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$filter_type = isset($_GET['type']) ? $_GET['type'] : '';
$filter_date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$filter_date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Build query with filters
$query = "SELECT t.*, u.username FROM transactions t JOIN users u ON t.user_id = u.id WHERE 1=1";
$params = [];
$types = "";

if ($filter_user > 0) {
    $query .= " AND t.user_id = ?";
    $params[] = $filter_user;
    $types .= "i";
}

if ($filter_type != '') {
    $query .= " AND t.type = ?";
    $params[] = $filter_type;
    $types .= "s";
}

if ($filter_date_from != '') {
    $query .= " AND DATE(t.created_at) >= ?";
    $params[] = $filter_date_from;
    $types .= "s";
}

if ($filter_date_to != '') {
    $query .= " AND DATE(t.created_at) <= ?";
    $params[] = $filter_date_to;
    $types .= "s";
}

$query .= " ORDER BY t.created_at DESC LIMIT 500";

// Execute query
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$transactions = [];
while ($row = $result->fetch_assoc()) {
    $transactions[] = $row;
}

// Get all users for filter dropdown
$users_query = "SELECT id, username FROM users ORDER BY username ASC";
$users_result = $db->query($users_query);
$users = [];
while ($row = $users_result->fetch_assoc()) {
    $users[$row['id']] = $row['username'];
}

// Calculate totals
$earn_total = 0;
$spend_total = 0;

foreach ($transactions as $transaction) {
    if ($transaction['type'] === 'earn') {
        $earn_total += $transaction['points'];
    } else {
        $spend_total += $transaction['points'];
    }
}

include 'header.php';
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Transaction History</h1>
        <a href="#" class="btn btn-sm btn-primary" onclick="window.print()">
            <i class="fas fa-download fa-sm text-white-50"></i> Export Report
        </a>
    </div>

    <!-- Filters Card -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Filters</h6>
        </div>
        <div class="card-body">
            <form method="get" action="transactions.php" class="row g-3">
                <div class="col-md-3">
                    <label for="user_id" class="form-label">User</label>
                    <select class="form-select" id="user_id" name="user_id">
                        <option value="">All Users</option>
                        <?php foreach ($users as $id => $username): ?>
                        <option value="<?php echo $id; ?>" <?php echo ($filter_user == $id) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($username); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="type" class="form-label">Transaction Type</label>
                    <select class="form-select" id="type" name="type">
                        <option value="">All Types</option>
                        <option value="earn" <?php echo ($filter_type == 'earn') ? 'selected' : ''; ?>>Earned</option>
                        <option value="spend" <?php echo ($filter_type == 'spend') ? 'selected' : ''; ?>>Spent</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="date_from" class="form-label">Date From</label>
                    <input type="date" class="form-control" id="date_from" name="date_from" value="<?php echo $filter_date_from; ?>">
                </div>
                <div class="col-md-3">
                    <label for="date_to" class="form-label">Date To</label>
                    <input type="date" class="form-control" id="date_to" name="date_to" value="<?php echo $filter_date_to; ?>">
                </div>
                <div class="col-12 mt-3">
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                    <a href="transactions.php" class="btn btn-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Summary Card -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Transactions
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo count($transactions); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exchange-alt fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Total Points Earned
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo formatPoints($earn_total); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-arrow-up fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                Total Points Spent
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo formatPoints($spend_total); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-arrow-down fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Transactions Table -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Transactions</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="transactionsTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Type</th>
                            <th>Points</th>
                            <th>Description</th>
                            <th>Reference</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($transactions) > 0): ?>
                            <?php foreach ($transactions as $transaction): ?>
                            <tr>
                                <td><?php echo $transaction['id']; ?></td>
                                <td>
                                    <a href="users.php?action=view&id=<?php echo $transaction['user_id']; ?>">
                                        <?php echo htmlspecialchars($transaction['username']); ?>
                                    </a>
                                </td>
                                <td>
                                    <?php if ($transaction['type'] === 'earn'): ?>
                                    <span class="badge bg-success">Earned</span>
                                    <?php else: ?>
                                    <span class="badge bg-secondary">Spent</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo formatPoints($transaction['points']); ?></td>
                                <td><?php echo htmlspecialchars($transaction['description']); ?></td>
                                <td>
                                    <?php 
                                    if ($transaction['reference_id'] && $transaction['reference_type']) {
                                        echo htmlspecialchars($transaction['reference_type'] . ' #' . $transaction['reference_id']);
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </td>
                                <td><?php echo formatDate($transaction['created_at']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center">No transactions found</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
