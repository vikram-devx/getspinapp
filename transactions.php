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

// Get all user transactions
$transactions = getUserTransactions($user_id, null); // null for no limit - get all

include 'includes/header.php';
?>

<div class="dashboard-container">
    <!-- Dashboard Sidebar - hidden on mobile -->
    <div class="dashboard-sidebar d-none d-md-block">
        <?php include 'includes/sidebar.php'; ?>
    </div>
    
    <!-- Dashboard Content -->
    <div class="dashboard-content">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center bg-white">
                <h5 class="mb-0">Transaction History</h5>
                <a href="<?php echo url('dashboard'); ?>" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
                </a>
            </div>
            <div class="card-body">
                <?php if (count($transactions) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Description</th>
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
                                <td><?php echo htmlspecialchars($transaction['description']); ?></td>
                                <td><?php echo formatPoints($transaction['points']); ?></td>
                                <td><?php echo formatDate($transaction['created_at']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="alert alert-info">
                    <p class="text-center mb-0">You don't have any transactions yet. Complete tasks to earn points!</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
    /* Mobile optimization for transactions page */
    @media (max-width: 767px) {
        .card-header h5 {
            font-size: 1.1rem;
        }
        
        .card-header .btn {
            font-size: 0.8rem;
            padding: 0.25rem 0.5rem;
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