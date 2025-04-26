<?php
// Include necessary files (session is started in config.php)
require_once '../includes/config.php';
require_once '../includes/db.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header('Location: ../login.php');
    exit;
}

// Upload directory for promo images
$uploadDir = '../assets/img/promo/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Process form submission
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle slide deletion
    if (isset($_POST['delete']) && isset($_POST['slide_id'])) {
        $slideId = filter_input(INPUT_POST, 'slide_id', FILTER_SANITIZE_NUMBER_INT);
        
        // Get the image path before deletion
        $stmt = $db->prepare("SELECT image_path FROM promo_slides WHERE id = ?");
        $stmt->execute([$slideId]);
        $imagePath = $stmt->fetchColumn();
        
        // Delete the slide
        $stmt = $db->prepare("DELETE FROM promo_slides WHERE id = ?");
        if ($stmt->execute([$slideId])) {
            // Delete the image file if it exists and is not the default image
            if (!empty($imagePath) && file_exists('../' . $imagePath) && strpos($imagePath, 'promo-default.jpg') === false) {
                unlink('../' . $imagePath);
            }
            
            $message = "Slide deleted successfully!";
            $messageType = "success";
        } else {
            $message = "Failed to delete slide.";
            $messageType = "danger";
        }
    }
    // Handle slide edit or creation
    else if (isset($_POST['save'])) {
        $slideId = filter_input(INPUT_POST, 'slide_id', FILTER_SANITIZE_NUMBER_INT);
        $title = htmlspecialchars(trim($_POST['title'] ?? ''));
        $description = htmlspecialchars(trim($_POST['description'] ?? ''));
        $buttonText = htmlspecialchars(trim($_POST['button_text'] ?? ''));
        $buttonUrl = htmlspecialchars(trim($_POST['button_url'] ?? ''));
        $active = isset($_POST['active']) ? 1 : 0;
        $displayOrder = filter_input(INPUT_POST, 'display_order', FILTER_SANITIZE_NUMBER_INT);
        
        // Handle image upload
        $imagePath = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $tempFile = $_FILES['image']['tmp_name'];
            $fileName = time() . '_' . basename($_FILES['image']['name']);
            $targetFile = $uploadDir . $fileName;
            
            // Check if the file is an image
            $check = getimagesize($tempFile);
            if ($check !== false) {
                // Move the uploaded file
                if (move_uploaded_file($tempFile, $targetFile)) {
                    $imagePath = 'assets/img/promo/' . $fileName;
                }
            }
        }
        
        // Update or insert slide
        if ($slideId) {
            // If editing, get the current image path if no new one was uploaded
            if (empty($imagePath)) {
                $stmt = $db->prepare("SELECT image_path FROM promo_slides WHERE id = ?");
                $stmt->execute([$slideId]);
                $imagePath = $stmt->fetchColumn();
            } else {
                // Delete the old image if a new one was uploaded
                $stmt = $db->prepare("SELECT image_path FROM promo_slides WHERE id = ?");
                $stmt->execute([$slideId]);
                $oldImagePath = $stmt->fetchColumn();
                
                if (!empty($oldImagePath) && file_exists('../' . $oldImagePath) && strpos($oldImagePath, 'promo-default.jpg') === false) {
                    unlink('../' . $oldImagePath);
                }
            }
            
            $stmt = $db->prepare("UPDATE promo_slides SET 
                title = ?, 
                description = ?, 
                button_text = ?, 
                button_url = ?, 
                image_path = ?, 
                active = ?,
                display_order = ?,
                updated_at = CURRENT_TIMESTAMP
                WHERE id = ?");
                
            $result = $stmt->execute([
                $title, 
                $description, 
                $buttonText, 
                $buttonUrl, 
                $imagePath, 
                $active,
                $displayOrder,
                $slideId
            ]);
            
            if ($result) {
                $message = "Slide updated successfully!";
                $messageType = "success";
            } else {
                $message = "Failed to update slide.";
                $messageType = "danger";
            }
        } else {
            // Use default image if none was uploaded
            if (empty($imagePath)) {
                $imagePath = 'assets/img/promo-default.jpg';
            }
            
            $stmt = $db->prepare("INSERT INTO promo_slides (
                title, 
                description, 
                button_text, 
                button_url, 
                image_path, 
                active,
                display_order
            ) VALUES (?, ?, ?, ?, ?, ?, ?)");
                
            $result = $stmt->execute([
                $title, 
                $description, 
                $buttonText, 
                $buttonUrl, 
                $imagePath, 
                $active,
                $displayOrder
            ]);
            
            if ($result) {
                $message = "Slide created successfully!";
                $messageType = "success";
            } else {
                $message = "Failed to create slide.";
                $messageType = "danger";
            }
        }
    }
}

// Get all slides
$stmt = $db->prepare("SELECT * FROM promo_slides ORDER BY display_order ASC, id ASC");
$stmt->execute();
$slides = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Include header
include 'header.php';

// Make sure jQuery and required libraries are loaded
echo '<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>';
echo '<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>';
echo '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">';
?>

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Manage Promo Slides</h1>
    
    <?php if (!empty($message)): ?>
    <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
        <?php echo $message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>
    
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Promo Slides</h6>
            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#slideModal">
                <i class="fas fa-plus"></i> Add New Slide
            </button>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="slidesTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Image</th>
                            <th>Title</th>
                            <th>Description</th>
                            <th>Button</th>
                            <th>Order</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($slides as $slide): ?>
                        <tr>
                            <td><?php echo $slide['id']; ?></td>
                            <td>
                                <img src="../<?php echo htmlspecialchars($slide['image_path']); ?>" alt="Slide Preview" 
                                     class="img-thumbnail" style="max-height: 75px;">
                            </td>
                            <td><?php echo htmlspecialchars($slide['title']); ?></td>
                            <td><?php echo htmlspecialchars(substr($slide['description'], 0, 50)) . (strlen($slide['description']) > 50 ? '...' : ''); ?></td>
                            <td>
                                <?php if (!empty($slide['button_text'])): ?>
                                <a href="<?php echo htmlspecialchars($slide['button_url']); ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                    <?php echo htmlspecialchars($slide['button_text']); ?>
                                </a>
                                <?php else: ?>
                                <span class="text-muted">No button</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $slide['display_order']; ?></td>
                            <td>
                                <span class="badge bg-<?php echo $slide['active'] ? 'success' : 'secondary'; ?>">
                                    <?php echo $slide['active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>
                            <td>
                                <button type="button" class="btn btn-sm btn-info edit-slide" 
                                        data-id="<?php echo $slide['id']; ?>"
                                        data-title="<?php echo htmlspecialchars($slide['title']); ?>"
                                        data-description="<?php echo htmlspecialchars($slide['description']); ?>"
                                        data-button-text="<?php echo htmlspecialchars($slide['button_text']); ?>"
                                        data-button-url="<?php echo htmlspecialchars($slide['button_url']); ?>"
                                        data-active="<?php echo $slide['active']; ?>"
                                        data-order="<?php echo $slide['display_order']; ?>"
                                        data-image="<?php echo htmlspecialchars($slide['image_path']); ?>">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <form method="post" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this slide?');">
                                    <input type="hidden" name="slide_id" value="<?php echo $slide['id']; ?>">
                                    <button type="submit" name="delete" class="btn btn-sm btn-danger">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($slides)): ?>
                        <tr>
                            <td colspan="8" class="text-center">No slides found. Create your first slide!</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Slide Modal -->
<div class="modal fade" id="slideModal" tabindex="-1" aria-labelledby="slideModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="post" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title" id="slideModalLabel">Add/Edit Promo Slide</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="slide_id" id="slide_id">
                    
                    <div class="mb-3">
                        <label for="title" class="form-label">Title</label>
                        <input type="text" class="form-control" name="title" id="title" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" name="description" id="description" rows="3"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="button_text" class="form-label">Button Text</label>
                                <input type="text" class="form-control" name="button_text" id="button_text">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="button_url" class="form-label">Button URL</label>
                                <input type="text" class="form-control" name="button_url" id="button_url">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="display_order" class="form-label">Display Order</label>
                                <input type="number" class="form-control" name="display_order" id="display_order" value="0" min="0">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3 form-check mt-4">
                                <input type="checkbox" class="form-check-input" name="active" id="active" checked>
                                <label class="form-check-label" for="active">Active</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="image" class="form-label">Slide Image</label>
                        <input type="file" class="form-control" name="image" id="image" accept="image/*">
                        <div id="image_preview_container" class="mt-2 d-none">
                            <p>Current Image:</p>
                            <img id="image_preview" src="" alt="Current Slide Image" class="img-thumbnail" style="max-height: 150px;">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="save" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        // Handle edit button click
        $('.edit-slide').on('click', function() {
            var id = $(this).data('id');
            var title = $(this).data('title');
            var description = $(this).data('description');
            var buttonText = $(this).data('button-text');
            var buttonUrl = $(this).data('button-url');
            var active = $(this).data('active');
            var order = $(this).data('order');
            var image = $(this).data('image');
            
            $('#slide_id').val(id);
            $('#title').val(title);
            $('#description').val(description);
            $('#button_text').val(buttonText);
            $('#button_url').val(buttonUrl);
            $('#active').prop('checked', active == 1);
            $('#display_order').val(order);
            
            if (image) {
                $('#image_preview').attr('src', '../' + image);
                $('#image_preview_container').removeClass('d-none');
            } else {
                $('#image_preview_container').addClass('d-none');
            }
            
            $('#slideModalLabel').text('Edit Promo Slide');
            $('#slideModal').modal('show');
        });
        
        // Reset form when modal is closed
        $('#slideModal').on('hidden.bs.modal', function() {
            $(this).find('form')[0].reset();
            $('#slide_id').val('');
            $('#image_preview_container').addClass('d-none');
            $('#slideModalLabel').text('Add New Promo Slide');
        });
        
        // Show image preview when a new file is selected
        $('#image').on('change', function() {
            var file = this.files[0];
            if (file) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    $('#image_preview').attr('src', e.target.result);
                    $('#image_preview_container').removeClass('d-none');
                }
                reader.readAsDataURL(file);
            }
        });
    });
</script>

<?php include 'footer.php'; ?>