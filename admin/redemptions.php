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

$message = '';
$message_type = '';

// Handle redemption actions
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    
    // Update redemption status
    if ($action === 'update_status' && isset($_GET['id']) && isset($_GET['status'])) {
        $redemption_id = (int)$_GET['id'];
        $status = $_GET['status'];
        
        // Validate status
        if (!in_array($status, ['pending', 'completed', 'rejected'])) {
            $message = 'Invalid status';
            $message_type = 'danger';
        } else {
            $result = updateRedemptionStatus($redemption_id, $status);
            
            if ($result['status'] === 'success') {
                $message = 'Redemption status updated successfully';
                $message_type = 'success';
            } else {
                $message = $result['message'];
                $message_type = 'danger';
            }
        }
    }
    
    // View redemption details
    if ($action === 'view' && isset($_GET['id'])) {
        $redemption_id = (int)$_GET['id'];
        
        try {
            // Get redemption details
            $stmt = $conn->prepare("
                SELECT r.*, u.username, u.email, rw.name as reward_name, rw.description as reward_description 
                FROM redemptions r
                JOIN users u ON r.user_id = u.id
                JOIN rewards rw ON r.reward_id = rw.id
                WHERE r.id = :redemption_id
            ");
            $stmt->bindValue(':redemption_id', $redemption_id, PDO::PARAM_INT);
            $stmt->execute();
            
            $redemption = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$redemption) {
                header('Location: redemptions.php');
                exit;
            }
        } catch (PDOException $e) {
            error_log("Error fetching redemption details: " . $e->getMessage());
            header('Location: redemptions.php');
            exit;
        }
    }
}

// Get all redemptions with filters
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$user_filter = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

// Build query with filters
$query = "
    SELECT r.*, u.username, rw.name as reward_name 
    FROM redemptions r
    JOIN users u ON r.user_id = u.id
    JOIN rewards rw ON r.reward_id = rw.id
    WHERE 1=1
";
$params = [];

if ($status_filter !== '') {
    $query .= " AND r.status = :status";
    $params[':status'] = $status_filter;
}

if ($user_filter > 0) {
    $query .= " AND r.user_id = :user_id";
    $params[':user_id'] = $user_filter;
}

$query .= " ORDER BY r.created_at DESC";

try {
    // Execute query
    $stmt = $conn->prepare($query);
    if (!empty($params)) {
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
    }
    $stmt->execute();
    
    $redemptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get all users for filter dropdown
    $users_query = "SELECT id, username FROM users ORDER BY username ASC";
    $stmt = $conn->query($users_query);
    $users = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $users[$row['id']] = $row['username'];
    }
} catch (PDOException $e) {
    error_log("Error fetching redemptions: " . $e->getMessage());
    $redemptions = [];
    $users = [];
}

include 'header.php';
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Manage Redemptions</h1>
    </div>

    <?php if ($message): ?>
    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
        <?php echo $message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <?php if (isset($action) && $action === 'view' && isset($redemption)): ?>
    <!-- Redemption Details View -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Redemption Details</h6>
            <a href="redemptions.php" class="btn btn-sm btn-secondary">Back to Redemptions</a>
        </div>
        <div class="card-body">
            <div class="row mb-4">
                <div class="col-md-6">
                    <h5>Redemption Information</h5>
                    <table class="table">
                        <tr>
                            <th>ID:</th>
                            <td><?php echo $redemption['id']; ?></td>
                        </tr>
                        <tr>
                            <th>Reward:</th>
                            <td><?php echo htmlspecialchars($redemption['reward_name']); ?></td>
                        </tr>
                        <tr>
                            <th>Description:</th>
                            <td><?php echo htmlspecialchars($redemption['reward_description']); ?></td>
                        </tr>
                        <tr>
                            <th>Points Used:</th>
                            <td><?php echo formatPoints($redemption['points_used']); ?></td>
                        </tr>
                        <?php if (!empty($redemption['redemption_details']) && ($redemption['reward_id'] == 6 || $redemption['reward_id'] == 7)): 
                            $details = json_decode($redemption['redemption_details'], true);
                            if ($details && isset($details['game_type']) && isset($details['username']) && isset($details['spins'])): 
                        ?>
                        <tr>
                            <th>Game Type:</th>
                            <td><?php echo htmlspecialchars($details['game_type']); ?></td>
                        </tr>
                        <tr>
                            <th>Game Username:</th>
                            <td><?php echo htmlspecialchars($details['username']); ?></td>
                        </tr>
                        <tr>
                            <th>Spins:</th>
                            <td><?php echo htmlspecialchars($details['spins']); ?></td>
                        </tr>
                        <?php endif; endif; ?>
                        <tr>
                            <th>Status:</th>
                            <td>
                                <?php if ($redemption['status'] === 'pending'): ?>
                                <span class="badge bg-warning">Pending</span>
                                <?php elseif ($redemption['status'] === 'completed'): ?>
                                <span class="badge bg-success">Completed</span>
                                <?php else: ?>
                                <span class="badge bg-danger">Rejected</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Date Requested:</th>
                            <td><?php echo formatDate($redemption['created_at']); ?></td>
                        </tr>
                        <tr>
                            <th>Last Updated:</th>
                            <td><?php echo formatDate($redemption['updated_at']); ?></td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <h5>User Information</h5>
                    <table class="table">
                        <tr>
                            <th>Username:</th>
                            <td><?php echo htmlspecialchars($redemption['username']); ?></td>
                        </tr>
                        <tr>
                            <th>Email:</th>
                            <td><?php echo htmlspecialchars($redemption['email']); ?></td>
                        </tr>
                        <tr>
                            <th>User ID:</th>
                            <td><?php echo $redemption['user_id']; ?></td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <h5>Update Status</h5>
                    <div class="card">
                        <div class="card-body">
                            <form class="row g-3">
                                <div class="col-md-4">
                                    <a href="redemptions.php?action=update_status&id=<?php echo $redemption['id']; ?>&status=completed" class="btn btn-success <?php echo $redemption['status'] === 'completed' ? 'disabled' : ''; ?>">
                                        <i class="fas fa-check-circle"></i> Mark as Completed
                                    </a>
                                </div>
                                <div class="col-md-4">
                                    <a href="redemptions.php?action=update_status&id=<?php echo $redemption['id']; ?>&status=rejected" class="btn btn-danger <?php echo $redemption['status'] === 'rejected' ? 'disabled' : ''; ?>">
                                        <i class="fas fa-times-circle"></i> Mark as Rejected
                                    </a>
                                </div>
                                <div class="col-md-4">
                                    <a href="redemptions.php?action=update_status&id=<?php echo $redemption['id']; ?>&status=pending" class="btn btn-warning <?php echo $redemption['status'] === 'pending' ? 'disabled' : ''; ?>">
                                        <i class="fas fa-clock"></i> Mark as Pending
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php else: ?>
    <!-- Redemptions List -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Filters</h6>
        </div>
        <div class="card-body">
            <form method="get" action="redemptions.php" class="row g-3">
                <div class="col-md-6">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">All Statuses</option>
                        <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="user_id" class="form-label">User</label>
                    <select class="form-select" id="user_id" name="user_id">
                        <option value="">All Users</option>
                        <?php foreach ($users as $id => $username): ?>
                        <option value="<?php echo $id; ?>" <?php echo $user_filter == $id ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($username); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 mt-3">
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                    <a href="redemptions.php" class="btn btn-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">All Redemptions</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="redemptionsTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Reward</th>
                            <th>Points</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($redemptions) > 0): ?>
                            <?php foreach ($redemptions as $redemption): ?>
                            <tr>
                                <td><?php echo $redemption['id']; ?></td>
                                <td>
                                    <a href="users.php?action=view&id=<?php echo $redemption['user_id']; ?>">
                                        <?php echo htmlspecialchars($redemption['username']); ?>
                                    </a>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($redemption['reward_name']); ?>
                                    <?php if ($redemption['reward_id'] == 6 || $redemption['reward_id'] == 7): ?>
                                        <span class="badge bg-info">Game Reward</span>
                                    <?php endif; ?>
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
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="redemptions.php?action=view&id=<?php echo $redemption['id']; ?>" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php if ($redemption['status'] === 'pending'): ?>
                                        <a href="redemptions.php?action=update_status&id=<?php echo $redemption['id']; ?>&status=completed" class="btn btn-sm btn-success">
                                            <i class="fas fa-check"></i>
                                        </a>
                                        <a href="redemptions.php?action=update_status&id=<?php echo $redemption['id']; ?>&status=rejected" class="btn btn-sm btn-danger">
                                            <i class="fas fa-times"></i>
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center">No redemptions found</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>
