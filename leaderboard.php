<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_once 'includes/db.php';

$auth = new Auth();
$current_user = $auth->getUser();

// Pagination setup
$users_per_page = 10;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$current_page = intval($current_page); // Convert to integer
$current_page = max(1, $current_page); // Ensure page is at least 1
$offset = ($current_page - 1) * $users_per_page;

// Fetch users for the current page
$db = Database::getInstance();
$conn = $db->getConnection();

// Get total number of non-admin users for pagination
$count_query = "SELECT COUNT(*) as total FROM users WHERE is_admin = 0";
$count_result = $conn->query($count_query);
$total_users = $count_result->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_users / $users_per_page);

// Base query for users by points with pagination
$query = "SELECT id, username, points, 
          (SELECT COUNT(*) FROM users u2 WHERE u2.points > u1.points) + 1 AS rank 
          FROM users u1 
          WHERE is_admin = 0 
          ORDER BY points DESC 
          LIMIT :limit OFFSET :offset";

$stmt = $conn->prepare($query);
$stmt->bindParam(':limit', $users_per_page, PDO::PARAM_INT);
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();

$leaderboard_users = [];
if ($stmt) {
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
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
// Skip including sidebar.php to avoid showing User Profile, Stats, and Quick Links cards
?>

<div class="container py-4">
    <div class="row">
        <div class="col-lg-9">
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
                    <h5 class="mb-0 me-2"><i class="fas fa-trophy text-warning me-2"></i>User Leaderboard</h5>
                    <?php if ($auth->isLoggedIn() && !$auth->isAdmin()): ?>
                        <div class="my-rank-badge">
                            <span class="badge bg-primary">Your Rank: <?php echo $current_user_rank['rank']; ?></span>
                            <span class="badge bg-info ms-lg-2"><?php echo formatPoints($current_user['points']); ?> Points</span>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="card-body px-4">
                    <div class="leaderboard-description mb-4">
                        <p class="text-muted">Earn more points to climb the ranks! Complete tasks and invite friends to boost your position.</p>
                    </div>
                    
                    <?php if (empty($leaderboard_users)): ?>
                        <div class="alert alert-info">
                            No users in the leaderboard yet. Be the first to earn points!
                        </div>
                    <?php else: ?>
                        <div class="table-responsive leaderboard-table-container">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th class="ps-3 rank-col">Rank</th>
                                        <th class="user-col">User</th>
                                        <th class="pe-3 points-col text-end">Points</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($leaderboard_users as $user): ?>
                                        <tr<?php echo ($auth->isLoggedIn() && $user['id'] == $current_user['id']) ? ' class="table-primary"' : ''; ?>>
                                            <td class="ps-3 rank-col">
                                                <?php if ($user['rank'] <= 3): ?>
                                                    <span class="rank-badge rank-<?php echo $user['rank']; ?>">
                                                        <?php if ($user['rank'] == 1): ?>
                                                            <i class="fas fa-trophy text-warning"></i>
                                                        <?php elseif ($user['rank'] == 2): ?>
                                                            <i class="fas fa-medal" style="color: #C0C0C0;"></i>
                                                        <?php else: ?>
                                                            <i class="fas fa-medal" style="color: #CD7F32;"></i>
                                                        <?php endif; ?>
                                                        <span class="rank-number"><?php echo $user['rank']; ?></span>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="rank-number"><?php echo $user['rank']; ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="user-col">
                                                <div class="d-flex align-items-center">
                                                    <div class="user-avatar me-2 bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                        <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                                                    </div>
                                                    <div class="user-info">
                                                        <span class="username"><?php echo htmlspecialchars($user['username']); ?></span>
                                                        <?php if ($auth->isLoggedIn() && $user['id'] == $current_user['id']): ?>
                                                            <span class="badge bg-secondary ms-2 user-badge">You</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="pe-3 points-col text-end">
                                                <strong class="points-value"><?php echo formatPoints($user['points']); ?></strong>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination Controls -->
                        <?php if ($total_pages > 1): ?>
                        <div class="pagination-container mt-4">
                            <nav aria-label="Leaderboard pagination">
                                <ul class="pagination justify-content-center">
                                    <!-- Previous Page Button -->
                                    <li class="page-item <?php echo ($current_page <= 1) ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="<?php echo ($current_page > 1) ? '/leaderboard?page=' . (intval($current_page) - 1) : '#'; ?>" aria-label="Previous">
                                            <span aria-hidden="true">&laquo;</span>
                                        </a>
                                    </li>
                                    
                                    <!-- Page Numbers -->
                                    <?php
                                    // Ensure current_page is an integer
                                    $current_page = intval($current_page);
                                    $start_page = max(1, $current_page - 2);
                                    $end_page = min($total_pages, $start_page + 4);
                                    
                                    if ($end_page - $start_page < 4 && $total_pages > 4) {
                                        $start_page = max(1, $end_page - 4);
                                    }
                                    
                                    for ($i = $start_page; $i <= $end_page; $i++): 
                                    ?>
                                        <li class="page-item <?php echo ($i == $current_page) ? 'active' : ''; ?>">
                                            <a class="page-link" href="/leaderboard?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <!-- Next Page Button -->
                                    <li class="page-item <?php echo ($current_page >= $total_pages) ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="<?php echo ($current_page < $total_pages) ? '/leaderboard?page=' . ($current_page + 1) : '#'; ?>" aria-label="Next">
                                            <span aria-hidden="true">&raquo;</span>
                                        </a>
                                    </li>
                                </ul>
                            </nav>
                        </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 mt-4 mt-lg-0">
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-chart-bar text-info me-2"></i>Leaderboard Stats</h5>
                </div>
                <div class="card-body px-4">
                    <div class="stats-item mb-3">
                        <div class="d-flex justify-content-between">
                            <span>Total Users</span>
                            <span class="fw-bold">
                                <?php 
                                $total_query = "SELECT COUNT(*) as count FROM users WHERE is_admin = 0";
                                $total_result = $conn->query($total_query);
                                $total_users = $total_result->fetch(PDO::FETCH_ASSOC)['count'];
                                echo $total_users;
                                ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="stats-item mb-3">
                        <div class="d-flex justify-content-between">
                            <span>Top Score</span>
                            <span class="fw-bold">
                                <?php 
                                $top_query = "SELECT MAX(points) as top_points FROM users WHERE is_admin = 0";
                                $top_result = $conn->query($top_query);
                                $top_points = $top_result->fetch(PDO::FETCH_ASSOC)['top_points'];
                                echo formatPoints($top_points ?? 0);
                                ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="stats-item">
                        <div class="d-flex justify-content-between">
                            <span>Average Points</span>
                            <span class="fw-bold">
                                <?php 
                                $avg_query = "SELECT AVG(points) as avg_points FROM users WHERE is_admin = 0";
                                $avg_result = $conn->query($avg_query);
                                $avg_points = $avg_result->fetch(PDO::FETCH_ASSOC)['avg_points'];
                                echo formatPoints($avg_points ?? 0);
                                ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-coins text-warning me-2"></i>How to Earn Points</h5>
                </div>
                <div class="card-body px-4">
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <i class="fas fa-check-circle text-success me-2"></i>
                            Complete available tasks
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check-circle text-success me-2"></i>
                            Refer new users (100 points per referral)
                        </li>
                        <li>
                            <i class="fas fa-check-circle text-success me-2"></i>
                            Stay active daily for bonuses
                        </li>
                    </ul>
                    
                    <div class="mt-3">
                        <a href="tasks.php" class="btn btn-primary w-100">Earn More Points</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* Leaderboard rank badges */
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
    
    /* Pagination styling */
    .pagination-container {
        margin-top: 1.5rem;
    }
    
    .pagination .page-link {
        color: #0d6efd;
        background-color: #fff;
        border-color: #dee2e6;
        padding: 0.5rem 0.75rem;
        transition: all 0.2s ease;
    }
    
    .pagination .page-link:hover {
        background-color: #f8f9fa;
        border-color: #dee2e6;
        color: #0a58ca;
        z-index: 2;
    }
    
    .pagination .page-item.active .page-link {
        background-color: #0d6efd;
        border-color: #0d6efd;
        color: white;
        font-weight: 500;
    }
    
    .pagination .page-item.disabled .page-link {
        color: #6c757d;
        pointer-events: none;
        background-color: #fff;
        border-color: #dee2e6;
    }
    
    .table-primary {
        background-color: rgba(13, 110, 253, 0.1) !important;
    }
    
    .user-avatar {
        font-weight: bold;
    }
    
    /* Add more spacing to leaderboard table */
    .table-responsive table {
        border-spacing: 0;
        border-collapse: separate;
        width: 100%;
    }
    
    .table-responsive table thead th {
        padding: 1rem 0.75rem;
        border-bottom: 2px solid #dee2e6;
        font-weight: 600;
    }
    
    .table-responsive table tbody td {
        padding: 0.85rem 0.75rem;
        vertical-align: middle;
    }
    
    .table-responsive table tbody tr {
        border-bottom: 1px solid #f5f5f5;
    }
    
    .table-responsive table tbody tr:hover {
        background-color: rgba(0, 123, 255, 0.03);
    }
    
    /* Mobile responsiveness improvements */
    @media (max-width: 576px) {
        .table-responsive {
            margin: 0 -15px; /* Negative margin to expand table to full width */
        }
        
        .table-responsive table {
            font-size: 0.875rem; /* Smaller font for mobile */
        }
        
        .table-responsive table thead th {
            padding: 0.75rem 0.5rem;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .table-responsive table tbody td {
            padding: 0.65rem 0.5rem;
        }
        
        /* Specific column widths for mobile */
        .rank-col {
            width: 15% !important;
            text-align: center;
        }
        
        .user-col {
            width: 60% !important;
        }
        
        .points-col {
            width: 25% !important;
        }
        
        .user-info {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }
        
        .username {
            font-size: 0.85rem;
            max-width: 120px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .user-badge {
            font-size: 0.6rem;
            padding: 0.25rem 0.5rem;
            margin-left: 0 !important;
            margin-top: 0.2rem;
        }
        
        .points-value {
            font-size: 0.85rem;
        }
        
        .rank-badge {
            padding: 3px 6px;
            font-size: 0.75rem;
        }
        
        .rank-number {
            font-size: 0.8rem;
        }
        
        .user-avatar {
            width: 32px !important;
            height: 32px !important;
            font-size: 0.875rem;
            flex-shrink: 0;
        }
        
        /* Adjust card padding for mobile */
        .card-body {
            padding: 1rem 0.75rem;
        }
        
        /* Make the "Your Rank" badge stack on mobile */
        .my-rank-badge {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 4px;
        }
        
        /* Optimize leaderboard description on mobile */
        .leaderboard-description p {
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
        }
        
        /* Adjust card headers for mobile */
        .card-header h5 {
            font-size: 1rem;
        }
        
        .stats-item span {
            font-size: 0.875rem;
        }
        
        /* Mobile pagination adjustments */
        .pagination .page-link {
            padding: 0.5rem 0.7rem;
            font-size: 0.9rem;
            margin: 0 2px;
        }
        
        .pagination-container {
            overflow-x: auto;
            padding: 1rem 0;
            margin: 0.5rem -0.75rem 0;
            background-color: #f8f9fa;
            border-top: 1px solid #dee2e6;
            display: flex;
            justify-content: center;
        }
        
        .pagination {
            flex-wrap: nowrap;
            min-width: min-content;
            margin-bottom: 0;
        }
        
        /* Make pagination stand out more */
        .pagination .page-item.active .page-link {
            box-shadow: 0 2px 5px rgba(13, 110, 253, 0.3);
        }
    }
</style>

<?php include 'includes/dashboard_footer.php'; ?>