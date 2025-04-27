<?php
require_once 'includes/header.php';

// Check if user is logged in
if (!$auth->isLoggedIn()) {
    // Redirect to login page
    header('Location: ' . url('login'));
    exit;
}

// Get the current user's data
$user_id = $current_user['id'];
$db = getDB();
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Process password change if form is submitted
$password_error = '';
$password_success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate current password
    if (!password_verify($current_password, $user['password'])) {
        $password_error = 'Current password is incorrect.';
    } 
    // Check if new password and confirm password match
    else if ($new_password !== $confirm_password) {
        $password_error = 'New password and confirm password do not match.';
    } 
    // Check if new password is strong enough
    else if (strlen($new_password) < 8) {
        $password_error = 'New password must be at least 8 characters long.';
    } 
    // Update the password
    else {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
        $result = $stmt->execute([$hashed_password, $user_id]);
        
        if ($result) {
            $password_success = 'Your password has been updated successfully.';
        } else {
            $password_error = 'Failed to update password. Please try again.';
        }
    }
}

// Get user statistics
$stmt = $db->prepare("SELECT COUNT(*) as total_completed FROM task_progress WHERE user_id = ? AND status = 'completed'");
$stmt->execute([$user_id]);
$total_completed = $stmt->fetch(PDO::FETCH_ASSOC)['total_completed'];

$stmt = $db->prepare("SELECT COUNT(*) as total_pending FROM task_progress WHERE user_id = ? AND status = 'pending'");
$stmt->execute([$user_id]);
$total_pending = $stmt->fetch(PDO::FETCH_ASSOC)['total_pending'];

$stmt = $db->prepare("SELECT SUM(points) as total_points FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$total_points = $stmt->fetch(PDO::FETCH_ASSOC)['total_points'];
?>

<div class="container py-4">
    <div class="row">
        <div class="col-lg-4 mb-4">
            <!-- User Profile Card -->
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <div class="mt-3 mb-4">
                        <div class="avatar-placeholder rounded-circle bg-primary text-white mx-auto" style="width: 100px; height: 100px;">
                            <i class="fas fa-user fa-3x"></i>
                        </div>
                    </div>
                    <h4><?php echo htmlspecialchars($user['username']); ?></h4>
                    <p class="text-muted"><?php echo htmlspecialchars($user['email']); ?></p>
                    <p>
                        <span class="badge bg-primary"><i class="fas fa-calendar-alt"></i> Joined <?php echo date('M Y', strtotime($user['created_at'])); ?></span>
                    </p>
                </div>
                <div class="card-footer">
                    <a href="<?php echo url('dashboard'); ?>" class="btn btn-primary btn-sm w-100 mb-2">
                        <i class="fas fa-tachometer-alt me-1"></i> Dashboard
                    </a>
                </div>
            </div>
            
            <!-- Quick Stats Card -->
            <div class="card shadow-sm mt-4 profile-stat-card">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Your Stats</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-3">
                        <div class="text-start">
                            <h6 class="mb-0">Points Earned</h6>
                        </div>
                        <div class="text-end">
                            <h6 class="mb-0 text-primary"><i class="fas fa-coins me-1"></i> <?php echo formatPoints($total_points); ?></h6>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <div class="text-start">
                            <h6 class="mb-0">Tasks Completed</h6>
                        </div>
                        <div class="text-end">
                            <h6 class="mb-0 text-success"><i class="fas fa-check-circle me-1"></i> <?php echo $total_completed; ?></h6>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between">
                        <div class="text-start">
                            <h6 class="mb-0">Tasks In Progress</h6>
                        </div>
                        <div class="text-end">
                            <h6 class="mb-0 text-warning"><i class="fas fa-clock me-1"></i> <?php echo $total_pending; ?></h6>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Quick Links Card -->
            <div class="card shadow-sm mt-4 profile-stat-card">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Quick Actions</h5>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center quick-action-link">
                            <a href="<?php echo url('tasks'); ?>" class="text-decoration-none text-dark w-100">
                                <i class="fas fa-tasks me-2 text-primary"></i> Earn Points
                            </a>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center quick-action-link">
                            <a href="<?php echo url('rewards'); ?>" class="text-decoration-none text-dark w-100">
                                <i class="fas fa-gift me-2 text-primary"></i> Rewards
                            </a>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center quick-action-link">
                            <a href="<?php echo url('leaderboard'); ?>" class="text-decoration-none text-dark w-100">
                                <i class="fas fa-trophy me-2 text-primary"></i> Leaderboard
                            </a>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center quick-action-link">
                            <a href="<?php echo url('referrals'); ?>" class="text-decoration-none text-dark w-100">
                                <i class="fas fa-users me-2 text-primary"></i> Referrals
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="col-lg-8">
            <!-- Account Settings Card -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Account Settings</h5>
                </div>
                <div class="card-body">
                    <form>
                        <div class="row mb-3">
                            <label for="username" class="col-sm-3 col-form-label">Username</label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control" id="username" value="<?php echo htmlspecialchars($user['username']); ?>" readonly>
                                <small class="text-muted">Username cannot be changed</small>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label for="email" class="col-sm-3 col-form-label">Email</label>
                            <div class="col-sm-9">
                                <input type="email" class="form-control" id="email" value="<?php echo htmlspecialchars($user['email']); ?>" readonly>
                                <small class="text-muted">Contact support to change your email</small>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label for="account_status" class="col-sm-3 col-form-label">Account Status</label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control" id="account_status" value="<?php echo $user['status'] === 'active' ? 'Active' : 'Inactive'; ?>" readonly>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Change Password Card -->
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Change Password</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($password_error)): ?>
                        <div class="alert alert-danger" role="alert">
                            <?php echo $password_error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($password_success)): ?>
                        <div class="alert alert-success" role="alert">
                            <?php echo $password_success; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="post" action="profile.php">
                        <div class="row mb-3">
                            <label for="current_password" class="col-sm-3 col-form-label">Current Password</label>
                            <div class="col-sm-9">
                                <input type="password" class="form-control" id="current_password" name="current_password" required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label for="new_password" class="col-sm-3 col-form-label">New Password</label>
                            <div class="col-sm-9">
                                <input type="password" class="form-control" id="new_password" name="new_password" required>
                                <small class="text-muted">Password must be at least 8 characters long</small>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label for="confirm_password" class="col-sm-3 col-form-label">Confirm New Password</label>
                            <div class="col-sm-9">
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-9 offset-sm-3">
                                <button type="submit" name="change_password" class="btn btn-primary">
                                    <i class="fas fa-key me-1"></i> Update Password
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>