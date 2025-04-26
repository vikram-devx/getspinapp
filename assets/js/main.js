$(document).ready(function() {
    
    // Initialize Bootstrap tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Task progress tracking
    function loadTaskProgress() {
        $.ajax({
            url: 'get_task_progress.php',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success' && response.progress && response.progress.length) {
                    updateTaskProgressUI(response.progress);
                } else {
                    // No tasks in progress
                    $('#no-tasks-message').show();
                }
            },
            error: function(xhr, status, error) {
                console.error('Error loading task progress:', error);
            }
        });
    }
    
    function updateTaskProgressUI(progressData) {
        // Clear container
        var container = $('#task-progress-container');
        container.empty();
        
        // If no tasks, show message
        if (!progressData || progressData.length === 0) {
            container.html('<p class="text-center text-muted" id="no-tasks-message">You don\'t have any active tasks.</p>');
            return;
        }
        
        // Create progress items
        progressData.forEach(function(task) {
            var progressPercent = task.current_progress || task.progress_percent || 0;
            var statusClass = 'info';
            var statusIcon = 'fas fa-sync-alt fa-spin';
            
            // Set appropriate classes based on status
            switch(task.status) {
                case 'completed':
                    statusClass = 'success';
                    statusIcon = 'fas fa-check-circle';
                    break;
                case 'failed':
                    statusClass = 'danger';
                    statusIcon = 'fas fa-times-circle';
                    break;
                case 'started':
                    statusClass = 'warning';
                    statusIcon = 'fas fa-hourglass-start';
                    break;
                case 'in_progress':
                    statusClass = 'info';
                    statusIcon = 'fas fa-spinner fa-spin';
                    break;
            }
            
            // Create progress item HTML
            var progressHtml = `
                <div class="task-progress-item mb-4" data-offer-id="${task.offer_id}">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="task-name fw-bold">Task #${task.offer_id}</span>
                        <span class="badge bg-${statusClass}"><i class="${statusIcon} me-1"></i> ${task.status.replace('_', ' ').toUpperCase()}</span>
                    </div>
                    <div class="progress mb-2" style="height: 10px;">
                        <div class="progress-bar progress-bar-striped bg-${statusClass}" role="progressbar" 
                            style="width: ${progressPercent}%" 
                            aria-valuenow="${progressPercent}" 
                            aria-valuemin="0" 
                            aria-valuemax="100">
                        </div>
                    </div>
                    <div class="d-flex justify-content-between small">
                        <span class="text-muted">${task.progress_message || 'Processing...'}</span>
                        <span class="text-muted">${progressPercent}%</span>
                    </div>
                </div>
            `;
            
            container.append(progressHtml);
            
            // For testing - add a simulate progress button if task is not completed or failed
            if (task.status !== 'completed' && task.status !== 'failed') {
                var simulateButton = `
                    <div class="simulate-controls mt-2 mb-3">
                        <div class="d-flex justify-content-between">
                            <button class="btn btn-sm btn-outline-primary simulate-progress" 
                                data-offer-id="${task.offer_id}" 
                                data-progress="${Math.min(progressPercent + 20, 100)}">
                                Simulate Progress
                            </button>
                            <button class="btn btn-sm btn-outline-danger simulate-fail" 
                                data-offer-id="${task.offer_id}">
                                Simulate Fail
                            </button>
                        </div>
                    </div>
                `;
                container.find(`.task-progress-item[data-offer-id="${task.offer_id}"]`).append(simulateButton);
            }
        });
        
        // Hide no tasks message
        $('#no-tasks-message').hide();
        
        // Add event listeners for simulation buttons
        $('.simulate-progress').on('click', function() {
            var offerId = $(this).data('offer-id');
            var progress = $(this).data('progress');
            simulateTaskProgress(offerId, progress, 'in_progress');
        });
        
        $('.simulate-fail').on('click', function() {
            var offerId = $(this).data('offer-id');
            simulateTaskProgress(offerId, 0, 'failed');
        });
    }
    
    function simulateTaskProgress(offerId, progress, status) {
        $.ajax({
            url: 'simulate_progress.php',
            type: 'GET',
            data: {
                offer_id: offerId,
                progress: progress,
                status: status,
                message: status === 'failed' ? 'Task failed. Please try again.' : 'Processing task...'
            },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    loadTaskProgress();
                }
            },
            error: function(xhr, status, error) {
                console.error('Error simulating progress:', error);
            }
        });
    }
    
    // Load initial task progress if on tasks page
    if ($('#task-progress-container').length) {
        loadTaskProgress();
        
        // Poll for updates every 5 seconds
        setInterval(loadTaskProgress, 5000);
    }
    
    // Copy Postback URL Button
    $('.copy-btn').on('click', function() {
        var textToCopy = $(this).data('copy');
        var tempInput = $('<input>');
        $('body').append(tempInput);
        tempInput.val(textToCopy).select();
        document.execCommand('copy');
        tempInput.remove();
        
        // Show copied feedback
        var originalText = $(this).text();
        $(this).text('Copied!');
        
        var btn = $(this);
        setTimeout(function() {
            btn.text(originalText);
        }, 2000);
    });
    
    // Form validation for register form
    $('#registerForm').on('submit', function(e) {
        var password = $('#password').val();
        var confirmPassword = $('#confirm_password').val();
        
        if (password !== confirmPassword) {
            e.preventDefault();
            $('#password-error').text('Passwords do not match').show();
            return false;
        }
        
        if (password.length < 6) {
            e.preventDefault();
            $('#password-error').text('Password must be at least 6 characters').show();
            return false;
        }
        
        return true;
    });
    
    // Task details modal
    $('.view-task').on('click', function() {
        var taskId = $(this).data('id');
        var taskTitle = $(this).data('title');
        var taskDescription = $(this).data('description');
        var taskRequirements = $(this).data('requirements');
        var taskPoints = $(this).data('points');
        var taskLink = $(this).data('link');
        
        // Populate modal with task details
        $('#taskModalLabel').text(taskTitle);
        $('#taskDescription').text(taskDescription);
        $('#taskRequirements').html(taskRequirements);
        $('#taskPoints').text(taskPoints);
        
        // Set the offer ID in the form's hidden input
        $('#taskForm').find('input[name="offer_id"]').val(taskId);
        
        // Store the offer link if available (for tracking purposes)
        if (taskLink) {
            // Make sure the form goes to tasks.php for tracking
            $('#taskForm').attr('action', 'tasks.php');
            
            // Set the offer link value in the hidden input
            $('#taskForm').find('input[name="offer_link"]').val(taskLink);
            
            // Set up direct link
            $('#directTaskLink').attr('href', taskLink);
            
            // Log to console for debugging
            console.log("Set offer link:", taskLink);
        } else {
            console.log("No offer link available for this task");
            $('#directTaskLink').attr('href', '#').text('No Task Link Available').addClass('disabled');
        }
        
        // Show the modal
        var taskModal = new bootstrap.Modal(document.getElementById('taskModal'));
        taskModal.show();
    });
    
    // Reward details modal
    $('.view-reward').on('click', function() {
        var rewardId = $(this).data('id');
        var rewardName = $(this).data('name');
        var rewardDescription = $(this).data('description');
        var rewardPoints = $(this).data('points');
        
        // Populate modal with reward details
        $('#rewardModalLabel').text(rewardName);
        $('#rewardDescription').text(rewardDescription);
        $('#rewardPoints').text(rewardPoints + ' points');
        $('#redeemForm').attr('action', 'redeem.php?reward_id=' + rewardId);
        
        // Check if this is a game spin reward (CoinMaster or Monopoly)
        if (rewardId == 6 || rewardId == 7) { // IDs for CoinMaster and Monopoly spin rewards
            $('#gameDetailsFields').show();
            $('#gameUsername').attr('required', true);
            
            // Set the placeholder text based on the game type
            if (rewardId == 6) {
                $('#gameUsername').attr('placeholder', 'Enter your CoinMaster username');
            } else {
                $('#gameUsername').attr('placeholder', 'Enter your Monopoly username');
            }
        } else {
            $('#gameDetailsFields').hide();
            $('#gameUsername').attr('required', false);
        }
        
        // Show the modal
        var rewardModal = new bootstrap.Modal(document.getElementById('rewardModal'));
        rewardModal.show();
    });
    
    // Admin reward form
    $('#rewardForm').on('submit', function(e) {
        var pointsRequired = $('#points_required').val();
        
        if (isNaN(pointsRequired) || pointsRequired <= 0) {
            e.preventDefault();
            $('#points-error').text('Points must be a positive number').show();
            return false;
        }
        
        return true;
    });
    
    // Confirm deletion
    $('.delete-btn').on('click', function(e) {
        if (!confirm('Are you sure you want to delete this item?')) {
            e.preventDefault();
            return false;
        }
        return true;
    });
    
    // Tab navigation
    $('a[data-bs-toggle="tab"]').on('shown.bs.tab', function(e) {
        var targetTab = $(e.target).attr('href');
        // Update URL with tab ID
        if (history.pushState) {
            history.pushState(null, null, targetTab);
        }
    });
    
    // Activate tab based on URL hash
    var hash = window.location.hash;
    if (hash) {
        $('.nav-tabs a[href="' + hash + '"]').tab('show');
    }
    
    // Handle task start action - using the direct link approach
    $('#directTaskLink').on('click', function(e) {
        // Don't prevent default behavior - let the link naturally open in a new tab
        
        // Show loading state
        $(this).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Loading...');
        $(this).addClass('disabled');
        
        // Get the offer link
        var offerLink = $(this).attr('href');
        
        if (offerLink && offerLink !== '#') {
            console.log("Opening offer link:", offerLink);
            
            // Submit the hidden form for tracking via AJAX
            setTimeout(function() {
                var formData = $('#taskForm').serialize();
                
                // Use AJAX to submit the form in the background
                $.ajax({
                    url: 'tasks.php',
                    data: formData,
                    type: 'GET',
                    success: function(response) {
                        console.log('Task tracking recorded');
                        
                        // After successful tracking, refresh the task progress display
                        if ($('#task-progress-container').length) {
                            loadTaskProgress();
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error recording task tracking:', error);
                    }
                });
            }, 100);
        } else {
            console.log("No valid offer link found");
            e.preventDefault(); // Prevent navigation for disabled links
        }
    });
    
    // Redemption confirmation
    $('.redeem-btn').on('click', function(e) {
        var userPoints = parseInt($(this).data('user-points'));
        var requiredPoints = parseInt($(this).data('required-points'));
        
        if (userPoints < requiredPoints) {
            e.preventDefault();
            alert('You do not have enough points to redeem this reward.');
            return false;
        }
        
        if (!confirm('Are you sure you want to redeem this reward?')) {
            e.preventDefault();
            return false;
        }
        
        return true;
    });
});
