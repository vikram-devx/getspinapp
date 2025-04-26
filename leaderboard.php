<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_once 'includes/db.php';

$auth = new Auth();
$current_user = $auth->getUser();

// Fetch top users ordered by points (limited to top 20)
$db = Database::getInstance();
$conn = $db->getConnection();

// Base query for top users by points
$query = "SELECT id, username, points, 
          (SELECT COUNT(*) FROM users u2 WHERE u2.points > u1.points) + 1 AS rank 
          FROM users u1 
          WHERE is_admin = 0 
          ORDER BY points DESC 
          LIMIT 20";

$result = $conn->query($query);
$leaderboard_users = [];

if ($result) {
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        $leaderboard_users[] = $row;
    }
}

// Get current user's rank if they're not in the top 20
$current_user_rank = null;
if ($auth->isLoggedIn() && !$auth->isAdmin()) {
    $find_rank_query = "SELECT (SELECT COUNT(*) FROM users u2 WHERE u2.points > u1.points) + 1 AS rank 
                       FROM users u1 WHERE id = :user_id";
    $stmt = $conn->prepare($find_rank_query);
    $stmt->execute(['user_id' => $current_user['id']]);
    $current_user_rank = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Include header
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="container py-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">User Leaderboard</h5>
                    <?php if ($auth->isLoggedIn() && !$auth->isAdmin()): ?>
                        <div class="my-rank-badge">
                            <span class="badge bg-primary">Your Rank: <?php echo $current_user_rank['rank']; ?></span>
                            <span class="badge bg-info ms-2"><?php echo formatPoints($current_user['points']); ?> Points</span>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <div class="leaderboard-description mb-4">
                        <p class="text-muted">Earn more points to climb the ranks! Complete tasks and invite friends to boost your position.</p>
                    </div>
                    
                    <?php if (empty($leaderboard_users)): ?>
                        <div class="alert alert-info">
                            No users in the leaderboard yet. Be the first to earn points!
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Rank</th>
                                        <th>User</th>
                                        <th>Points</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($leaderboard_users as $user): ?>
                                        <tr<?php echo ($auth->isLoggedIn() && $user['id'] == $current_user['id']) ? ' class="table-primary"' : ''; ?>>
                                            <td>
                                                <?php if ($user['rank'] <= 3): ?>
                                                    <span class="rank-badge rank-<?php echo $user['rank']; ?>">
                                                        <?php if ($user['rank'] == 1): ?>
                                                            <i class="fas fa-trophy text-warning"></i>
                                                        <?php elseif ($user['rank'] == 2): ?>
                                                            <i class="fas fa-medal" style="color: #C0C0C0;"></i>
                                                        <?php else: ?>
                                                            <i class="fas fa-medal" style="color: #CD7F32;"></i>
                                                        <?php endif; ?>
                                                        <?php echo $user['rank']; ?>
                                                    </span>
                                                <?php else: ?>
                                                    <?php echo $user['rank']; ?>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="user-avatar me-2 bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                        <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                                                    </div>
                                                    <span><?php echo htmlspecialchars($user['username']); ?></span>
                                                    <?php if ($auth->isLoggedIn() && $user['id'] == $current_user['id']): ?>
                                                        <span class="badge bg-secondary ms-2">You</span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <strong><?php echo formatPoints($user['points']); ?></strong>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .rank-badge {
        padding: 5px 8px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
    }
    
    .rank-1 {
        background-color: rgba(255, 215, 0, 0.2);
        color: #ffc107;
    }
    
    .rank-2 {
        background-color: rgba(192, 192, 192, 0.2);
        color: #6c757d;
    }
    
    .rank-3 {
        background-color: rgba(205, 127, 50, 0.2);
        color: #CD7F32;
    }
    
    .table-primary {
        background-color: rgba(13, 110, 253, 0.1) !important;
    }
    
    .user-avatar {
        font-weight: bold;
    }
</style>

<?php include 'includes/footer.php'; ?>