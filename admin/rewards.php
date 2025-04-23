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

// Handle reward actions
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    
    // Add new reward
    if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = isset($_POST['name']) ? trim($_POST['name']) : '';
        $description = isset($_POST['description']) ? trim($_POST['description']) : '';
        $points_required = isset($_POST['points_required']) ? (int)$_POST['points_required'] : 0;
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        if (empty($name)) {
            $message = 'Reward name is required';
            $message_type = 'danger';
        } else if ($points_required <= 0) {
            $message = 'Points required must be greater than zero';
            $message_type = 'danger';
        } else {
            $result = addReward($name, $description, $points_required, $is_active);
            
            if ($result['status'] === 'success') {
                $message = 'Reward added successfully';
                $message_type = 'success';
            } else {
                $message = $result['message'];
                $message_type = 'danger';
            }
        }
    }
    
    // Edit reward
    if ($action === 'edit' && isset($_GET['id'])) {
        $reward_id = (int)$_GET['id'];
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = isset($_POST['name']) ? trim($_POST['name']) : '';
            $description = isset($_POST['description']) ? trim($_POST['description']) : '';
            $points_required = isset($_POST['points_required']) ? (int)$_POST['points_required'] : 0;
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            
            if (empty($name)) {
                $message = 'Reward name is required';
                $message_type = 'danger';
            } else if ($points_required <= 0) {
                $message = 'Points required must be greater than zero';
                $message_type = 'danger';
            } else {
                $result = updateReward($reward_id, $name, $description, $points_required, $is_active);
                
                if ($result['status'] === 'success') {
                    $message = 'Reward updated successfully';
                    $message_type = 'success';
                } else {
                    $message = $result['message'];
                    $message_type = 'danger';
                }
            }
        }
        
        // Get reward details for the form
        $stmt = $conn->prepare("SELECT * FROM rewards WHERE id = :reward_id");
        $stmt->bindValue(':reward_id', $reward_id, PDO::PARAM_INT);
        $stmt->execute();
        
        $reward = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$reward) {
            header('Location: rewards.php');
            exit;
        }
    }
    
    // Delete reward
    if ($action === 'delete' && isset($_GET['id'])) {
        $reward_id = (int)$_GET['id'];
        $result = deleteReward($reward_id);
        
        if ($result['status'] === 'success') {
            $message = 'Reward deleted successfully';
            $message_type = 'success';
        } else {
            $message = $result['message'];
            $message_type = 'danger';
        }
    }
}

// Get all rewards
$rewards = getAllRewards();

include 'header.php';
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Manage Rewards</h1>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addRewardModal">
            <i class="fas fa-plus-circle fa-sm"></i> Add New Reward
        </button>
    </div>

    <?php if ($message): ?>
    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
        <?php echo $message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <!-- Rewards List -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">All Rewards</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="rewardsTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Description</th>
                            <th>Points Required</th>
                            <th>Status</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rewards as $reward): ?>
                        <tr>
                            <td><?php echo $reward['id']; ?></td>
                            <td><?php echo htmlspecialchars($reward['name']); ?></td>
                            <td><?php echo htmlspecialchars(substr($reward['description'], 0, 50) . (strlen($reward['description']) > 50 ? '...' : '')); ?></td>
                            <td><?php echo formatPoints($reward['points_required']); ?></td>
                            <td>
                                <?php if ($reward['is_active']): ?>
                                <span class="badge bg-success">Active</span>
                                <?php else: ?>
                                <span class="badge bg-secondary">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo formatDate($reward['created_at']); ?></td>
                            <td>
                                <a href="rewards.php?action=edit&id=<?php echo $reward['id']; ?>" class="btn btn-sm btn-primary">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="rewards.php?action=delete&id=<?php echo $reward['id']; ?>" class="btn btn-sm btn-danger delete-btn">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Reward Modal -->
<div class="modal fade" id="addRewardModal" tabindex="-1" aria-labelledby="addRewardModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addRewardModalLabel">Add New Reward</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="rewards.php?action=add" method="post" id="rewardForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="name" class="form-label">Reward Name</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="points_required" class="form-label">Points Required</label>
                        <input type="number" class="form-control" id="points_required" name="points_required" min="1" required>
                        <div id="points-error" class="invalid-feedback" style="display: none;"></div>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="is_active" name="is_active" checked>
                        <label class="form-check-label" for="is_active">Active</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Reward</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if (isset($action) && $action === 'edit' && isset($reward)): ?>
<!-- Edit Reward Modal -->
<div class="modal fade" id="editRewardModal" tabindex="-1" aria-labelledby="editRewardModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editRewardModalLabel">Edit Reward</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="rewards.php?action=edit&id=<?php echo $reward['id']; ?>" method="post">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_name" class="form-label">Reward Name</label>
                        <input type="text" class="form-control" id="edit_name" name="name" value="<?php echo htmlspecialchars($reward['name']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_description" class="form-label">Description</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="3"><?php echo htmlspecialchars($reward['description']); ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="edit_points_required" class="form-label">Points Required</label>
                        <input type="number" class="form-control" id="edit_points_required" name="points_required" min="1" value="<?php echo $reward['points_required']; ?>" required>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="edit_is_active" name="is_active" <?php echo $reward['is_active'] ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="edit_is_active">Active</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Reward</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Show edit modal automatically when in edit mode
    document.addEventListener('DOMContentLoaded', function() {
        var editModal = new bootstrap.Modal(document.getElementById('editRewardModal'));
        editModal.show();
    });
</script>
<?php endif; ?>

<script>
    // Confirm delete
    document.addEventListener('DOMContentLoaded', function() {
        const deleteButtons = document.querySelectorAll('.delete-btn');
        deleteButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                if (!confirm('Are you sure you want to delete this reward?')) {
                    e.preventDefault();
                }
            });
        });
    });
</script>

<?php include 'footer.php'; ?>
