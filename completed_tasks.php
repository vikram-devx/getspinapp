<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Check if user is logged in
session_start();
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$message = '';
$message_type = 'info';

try {
    // Get user's completed offers
    $stmt = $conn->prepare("
        SELECT uo.*, u.username 
        FROM user_offers uo 
        JOIN users u ON uo.user_id = u.id 
        WHERE uo.user_id = :user_id AND uo.completed = 1 
        ORDER BY uo.completion_time DESC
    ");
    $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $completed_tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message = 'Error retrieving completed tasks: ' . $e->getMessage();
    $message_type = 'danger';
    $completed_tasks = [];
}

include 'includes/header.php';
?>

<div class="row">
    <div class="col-lg-8 mx-auto">
        <div class="card mb-4">
            <div class="card-header bg-success text-white">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-check-circle me-2"></i>All Completed Tasks</h5>
                    <a href="tasks.php" class="btn btn-sm btn-light">
                        <i class="fas fa-arrow-left me-1"></i> Back to Tasks
                    </a>
                </div>
            </div>
            <div class="card-body">
                <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?>"><?php echo $message; ?></div>
                <?php endif; ?>
                
                <?php if (empty($completed_tasks)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    You haven't completed any tasks yet. Go to the <a href="tasks.php">Tasks page</a> to get started!
                </div>
                <?php else: ?>
                
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Task Name</th>
                                <th>Completed On</th>
                                <th class="text-end">Points Earned</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($completed_tasks as $task): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($task['offer_name'] ?: 'Task #' . $task['offer_id']); ?></strong>
                                    <div class="small text-muted">ID: <?php echo $task['offer_id']; ?></div>
                                </td>
                                <td>
                                    <?php 
                                    $completion_time = $task['completion_time'] ?: $task['last_updated'];
                                    echo date('M j, Y g:i A', strtotime($completion_time)); 
                                    ?>
                                </td>
                                <td class="text-end text-success fw-bold">
                                    +<?php echo formatPoints($task['points_earned']); ?> points
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

<?php include 'includes/dashboard_footer.php'; ?>