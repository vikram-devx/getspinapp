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

// Handle user actions
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    
    // View user details
    if ($action === 'view' && isset($_GET['id'])) {
        $user_id = (int)$_GET['id'];
        
        // Get user details
        $stmt = $conn->prepare("SELECT * FROM users WHERE id = :user_id");
        $stmt->bindValue(':user_id', $user_id);
        $stmt->execute();
        
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            header('Location: users.php');
            exit;
        }
        
        // Get user stats
        $stats = getUserStats($user_id);
        
        // Get user transactions
        $stmt = $conn->prepare("SELECT * FROM transactions WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 10");
        $stmt->bindValue(':user_id', $user_id);
        $stmt->execute();
        
        $transactions = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $transactions[] = $row;
        }
        
        // Get user redemptions
        $stmt = $conn->prepare("SELECT r.*, r.reward_id, r.redemption_details, rw.name as reward_name FROM redemptions r JOIN rewards rw ON r.reward_id = rw.id WHERE r.user_id = :user_id ORDER BY r.created_at DESC");
        $stmt->bindValue(':user_id', $user_id);
        $stmt->execute();
        
        $redemptions = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $redemptions[] = $row;
        }
    }
    
    // Edit user's points
    if ($action === 'edit_points' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['id'])) {
        $user_id = (int)$_GET['id'];
        $points = isset($_POST['points']) ? (int)$_POST['points'] : 0;
        $type = isset($_POST['type']) ? $_POST['type'] : 'add';
        $description = isset($_POST['description']) ? trim($_POST['description']) : 'Admin adjustment';
        
        // Get current user points
        $stmt = $conn->prepare("SELECT points FROM users WHERE id = :user_id");
        $stmt->bindValue(':user_id', $user_id);
        $stmt->execute();
        
        $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user_data) {
            $message = 'User not found';
            $message_type = 'danger';
        } else {
            $current_points = $user_data['points'];
            
            // Validate points adjustment
            if ($points <= 0) {
                $message = 'Points must be greater than zero';
                $message_type = 'danger';
            } else if ($type === 'remove' && $points > $current_points) {
                $message = 'Cannot remove more points than the user has';
                $message_type = 'danger';
            } else {
                // Perform points adjustment
                if ($type === 'add') {
                    $result = $auth->updatePoints($user_id, $points, 'earn', $description, null, 'admin');
                } else {
                    $result = $auth->updatePoints($user_id, $points, 'spend', $description, null, 'admin');
                }
                
                if ($result['status'] === 'success') {
                    $message = 'Points adjusted successfully';
                    $message_type = 'success';
                } else {
                    $message = $result['message'];
                    $message_type = 'danger';
                }
            }
        }
    }
    
    // Change user role
    if ($action === 'change_role' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['id'])) {
        $user_id = (int)$_GET['id'];
        $is_admin = isset($_POST['is_admin']) ? 1 : 0;
        
        $stmt = $conn->prepare("UPDATE users SET is_admin = :is_admin WHERE id = :user_id");
        $stmt->bindValue(':is_admin', $is_admin, PDO::PARAM_INT);
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            $message = 'User role updated successfully';
            $message_type = 'success';
        } else {
            $message = 'Failed to update user role';
            $message_type = 'danger';
        }
    }
    
    // Block/Unblock user
    if ($action === 'toggle_block' && isset($_GET['id'])) {
        $user_id = (int)$_GET['id'];
        
        // Get current block status
        $stmt = $conn->prepare("SELECT is_blocked FROM users WHERE id = :user_id");
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user_data) {
            // Toggle the block status
            $new_status = $user_data['is_blocked'] ? 0 : 1;
            $stmt = $conn->prepare("UPDATE users SET is_blocked = :is_blocked WHERE id = :user_id");
            $stmt->bindValue(':is_blocked', $new_status, PDO::PARAM_INT);
            $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
            
            if ($stmt->execute()) {
                $message = $new_status ? 'User blocked successfully' : 'User unblocked successfully';
                $message_type = 'success';
                
                // Create an admin notification
                $notification_title = $new_status ? 'User Blocked' : 'User Unblocked';
                $username = getUsernameById($user_id);
                $notification_message = $new_status 
                    ? "User {$username} (ID: {$user_id}) has been blocked." 
                    : "User {$username} (ID: {$user_id}) has been unblocked.";
                
                createAdminNotification(
                    $notification_title,
                    $notification_message,
                    'user_' . ($new_status ? 'blocked' : 'unblocked'),
                    ['user_id' => $user_id]
                );
            } else {
                $message = 'Failed to update user status';
                $message_type = 'danger';
            }
        } else {
            $message = 'User not found';
            $message_type = 'danger';
        }
        
        // Redirect back to the user view
        header("Location: users.php?action=view&id={$user_id}");
        exit;
    }
}

// Get all users
$users = getAllUsers();

include 'header.php';
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Manage Users</h1>
    </div>

    <?php if ($message): ?>
    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
        <?php echo $message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <?php if (isset($action) && $action === 'view' && isset($user)): ?>
    <!-- User Details View -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">User Details: <?php echo htmlspecialchars($user['username']); ?></h6>
            <div>
                <?php 
                // Check if user is blocked and display appropriate button
                $is_blocked = isset($user['is_blocked']) && $user['is_blocked'] == 1;
                $block_btn_class = $is_blocked ? 'btn-success' : 'btn-danger';
                $block_btn_text = $is_blocked ? 'Unblock User' : 'Block User';
                $block_btn_icon = $is_blocked ? 'fa-unlock' : 'fa-user-slash';
                ?>
                <a href="users.php?action=toggle_block&id=<?php echo $user['id']; ?>" 
                   class="btn btn-sm <?php echo $block_btn_class; ?> me-2" 
                   onclick="return confirm('Are you sure you want to <?php echo $is_blocked ? 'unblock' : 'block'; ?> this user?');">
                    <i class="fas <?php echo $block_btn_icon; ?> me-1"></i> <?php echo $block_btn_text; ?>
                </a>
                <a href="users.php" class="btn btn-sm btn-secondary">Back to Users</a>
            </div>
        </div>
        <div class="card-body">
            <div class="row mb-4">
                <div class="col-md-6">
                    <h5>Basic Information</h5>
                    <table class="table">
                        <tr>
                            <th>Username:</th>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                        </tr>
                        <tr>
                            <th>Email:</th>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                        </tr>
                        <tr>
                            <th>Points:</th>
                            <td><?php echo formatPoints($user['points']); ?></td>
                        </tr>
                        <tr>
                            <th>Role:</th>
                            <td>
                                <?php if ($user['is_admin']): ?>
                                <span class="badge bg-danger">Admin</span>
                                <?php else: ?>
                                <span class="badge bg-primary">User</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Member Since:</th>
                            <td><?php echo formatDate($user['created_at']); ?></td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <h5>User Statistics</h5>
                    <table class="table">
                        <tr>
                            <th>Total Earned:</th>
                            <td><?php echo formatPoints($stats['total_earned']); ?></td>
                        </tr>
                        <tr>
                            <th>Total Spent:</th>
                            <td><?php echo formatPoints($stats['total_spent']); ?></td>
                        </tr>
                        <tr>
                            <th>Completed Offers:</th>
                            <td><?php echo $stats['completed_offers']; ?></td>
                        </tr>
                        <tr>
                            <th>Redeemed Rewards:</th>
                            <td><?php echo $stats['redeemed_rewards']; ?></td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-md-6">
                    <h5>Adjust Points</h5>
                    <form action="users.php?action=edit_points&id=<?php echo $user['id']; ?>" method="post">
                        <div class="mb-3">
                            <label for="points" class="form-label">Points</label>
                            <input type="number" class="form-control" id="points" name="points" min="1" required>
                        </div>
                        <div class="mb-3">
                            <label for="type" class="form-label">Action</label>
                            <select class="form-select" id="type" name="type" required>
                                <option value="add">Add Points</option>
                                <option value="remove">Remove Points</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <input type="text" class="form-control" id="description" name="description" value="Admin adjustment">
                        </div>
                        <button type="submit" class="btn btn-primary">Adjust Points</button>
                    </form>
                </div>
                <div class="col-md-6">
                    <h5>Change User Role</h5>
                    <form action="users.php?action=change_role&id=<?php echo $user['id']; ?>" method="post">
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="is_admin" name="is_admin" <?php echo $user['is_admin'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="is_admin">Administrator</label>
                        </div>
                        <p class="small text-muted">Administrators have access to the admin panel and full control over the application.</p>
                        <button type="submit" class="btn btn-warning">Update Role</button>
                    </form>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <ul class="nav nav-tabs" id="userTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="transactions-tab" data-bs-toggle="tab" data-bs-target="#transactions" type="button" role="tab" aria-controls="transactions" aria-selected="true">Transactions</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="redemptions-tab" data-bs-toggle="tab" data-bs-target="#redemptions" type="button" role="tab" aria-controls="redemptions" aria-selected="false">Redemptions</button>
                        </li>
                    </ul>
                    <div class="tab-content" id="userTabsContent">
                        <div class="tab-pane fade show active" id="transactions" role="tabpanel" aria-labelledby="transactions-tab">
                            <div class="table-responsive mt-3">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Type</th>
                                            <th>Points</th>
                                            <th>Description</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (count($transactions) > 0): ?>
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
                                                <td><?php echo htmlspecialchars($transaction['description']); ?></td>
                                                <td><?php echo formatDate($transaction['created_at']); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center">No transactions found</td>
                                        </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="redemptions" role="tabpanel" aria-labelledby="redemptions-tab">
                            <div class="table-responsive mt-3">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Reward</th>
                                            <th>Points Used</th>
                                            <th>Status</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (count($redemptions) > 0): ?>
                                            <?php foreach ($redemptions as $redemption): ?>
                                            <tr>
                                                <td>
                                                    <?php echo htmlspecialchars($redemption['reward_name']); ?>
                                                    <?php if (($redemption['reward_id'] == 6 || $redemption['reward_id'] == 7) && !empty($redemption['redemption_details'])): 
                                                        $details = json_decode($redemption['redemption_details'], true);
                                                        if ($details && isset($details['username']) && isset($details['spins'])): 
                                                    ?>
                                                    <span class="badge bg-info">Game Reward</span>
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
                                        <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center">No redemptions found</td>
                                        </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php else: ?>
    <!-- Users List -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">All Users</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="usersTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Points</th>
                            <th>Role</th>
                            <th>Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo $user['id']; ?></td>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo formatPoints($user['points']); ?></td>
                            <td>
                                <?php if ($user['is_admin']): ?>
                                <span class="badge bg-danger">Admin</span>
                                <?php else: ?>
                                <span class="badge bg-primary">User</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo formatDate($user['created_at']); ?></td>
                            <td>
                                <a href="users.php?action=view&id=<?php echo $user['id']; ?>" class="btn btn-sm btn-info">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>
