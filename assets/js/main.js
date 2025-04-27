$(document).ready(function() {
    // Promo Slider Functionality
    if ($('.promo-slider').length) {
        var currentSlide = 0;
        var slides = $('.promo-slide');
        var totalSlides = slides.length;
        var slideWidth = 100; // percentage
        var autoSlideInterval;
        
        // Initialize slider if multiple slides exist
        if (totalSlides > 1) {
            // Start auto-sliding
            startAutoSlide();
            
            // Handle dot click
            $(document).on('click', '.slider-dot', function() {
                var index = $(this).data('index');
                goToSlide(index);
                resetAutoSlide();
            });
            
            // Handle arrow clicks
            $('.slider-arrow-left').on('click', function() {
                goToSlide(currentSlide - 1);
                resetAutoSlide();
            });
            
            $('.slider-arrow-right').on('click', function() {
                goToSlide(currentSlide + 1);
                resetAutoSlide();
            });
        }
        
        // Function to go to a specific slide
        function goToSlide(slideIndex) {
            // Handle circular navigation
            if (slideIndex < 0) {
                slideIndex = totalSlides - 1;
            } else if (slideIndex >= totalSlides) {
                slideIndex = 0;
            }
            
            // Update currentSlide
            currentSlide = slideIndex;
            
            // Move to the correct slide
            $('.promo-slides').css('transform', 'translateX(-' + (slideIndex * slideWidth) + '%)');
            
            // Update active dot
            $('.slider-dot').removeClass('active');
            $('.slider-dot[data-index="' + slideIndex + '"]').addClass('active');
        }
        
        // Auto-slide functionality
        function startAutoSlide() {
            autoSlideInterval = setInterval(function() {
                goToSlide(currentSlide + 1);
            }, 5000); // Change slide every 5 seconds
        }
        
        function resetAutoSlide() {
            clearInterval(autoSlideInterval);
            startAutoSlide();
        }
    }
    
    
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
            
            // Simulate functionality has been removed
        });
        
        // Hide no tasks message
        $('#no-tasks-message').hide();
        
        // Simulation functionality has been removed
    }
    
    // simulateTaskProgress function has been removed
    
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
        var taskAdCopy = $(this).data('adcopy');
        var taskPoints = $(this).data('points');
        var taskLink = $(this).data('link');
        var taskImage = $(this).data('image');
        var taskType = $(this).data('type');
        var taskDevice = $(this).data('device');
        
        // Populate modal with task details
        $('#taskModalLabel').text(taskTitle);
        $('#taskDescription').text(taskDescription);
        $('#taskRequirements').html(taskRequirements);
        
        // Hide Instructions section if it's identical to the description
        if (taskAdCopy && taskDescription && taskAdCopy.trim() === taskDescription.trim()) {
            // Hide the entire Instructions section
            $('.task-details-container .mb-3').eq(2).hide();
        } else {
            // Show Instructions section and set content
            $('.task-details-container .mb-3').eq(2).show();
            $('#taskAdCopy').html(taskAdCopy);
        }
        
        $('#taskPoints').text(taskPoints);
        
        // Handle device compatibility icons
        if (taskDevice) {
            var deviceIcons = '';
            var deviceString = taskDevice.toLowerCase();
            
            if (deviceString.includes('android')) {
                deviceIcons += '<span class="device-badge device-android me-2"><i class="fab fa-android"></i></span>';
            }
            
            if (deviceString.includes('iphone') || deviceString.includes('ipad') || deviceString.includes('ios')) {
                deviceIcons += '<span class="device-badge device-ios me-2"><i class="fab fa-apple"></i></span>';
            }
            
            if (deviceString.includes('desktop')) {
                deviceIcons += '<span class="device-badge me-2"><i class="fas fa-desktop"></i></span>';
            }
            
            $('#taskDeviceCompat').html(deviceIcons);
        } else {
            $('#taskDeviceCompat').html('');
        }
        
        // Handle task image
        if (taskImage && taskImage !== '') {
            $('#taskImage').attr('src', taskImage).show();
            $('#taskImageCol').show();
            
            // Set the task type badge
            var typeLabel, typeIcon;
            
            if (taskType.toLowerCase() === 'cpi') {
                typeLabel = 'APP INSTALL';
                typeIcon = '<i class="fas fa-mobile-alt me-1"></i>';
            } else if (taskType.toLowerCase() === 'cpa') {
                typeLabel = 'SURVEY OFFER';
                typeIcon = '<i class="fas fa-poll me-1"></i>';
            } else {
                typeLabel = taskType.toUpperCase();
                typeIcon = '<i class="fas fa-tasks me-1"></i>';
            }
            
            $('#taskTypeTag').html(typeIcon + ' ' + typeLabel).removeClass().addClass('task-type-tag').addClass('offer-type-' + taskType.toLowerCase());
        } else {
            $('#taskImageCol').hide();
        }
        
        // Set the offer ID in the form's hidden input
        $('#taskForm').find('input[name="offer_id"]').val(taskId);
        
        // Store the offer link if available (for tracking purposes)
        if (taskLink) {
            // Make sure the form goes to tasks.php for tracking
            $('#taskForm').attr('action', 'tasks.php');
            
            // Set the offer link value in the hidden input
            $('#taskForm').find('input[name="offer_link"]').val(taskLink);
            
            // Set up direct link
            $('#directTaskLink').attr('href', taskLink).text('Start Task').removeClass('disabled');
            
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
            showNotification('error', 'Insufficient Points', 'You do not have enough points to redeem this reward.');
            return false;
        }
        
        var gameUsername = $('#gameUsername').val();
        var gameRequired = $('#gameUsername').prop('required');
        
        if (gameRequired && (!gameUsername || gameUsername.trim() === '')) {
            e.preventDefault();
            showNotification('error', 'Missing Information', 'Please enter your game username.');
            $('#gameUsername').focus();
            return false;
        }
        
        var confirmText = $('#confirmRedeem').val();
        if (confirmText !== 'CONFIRM') {
            e.preventDefault();
            showNotification('error', 'Confirmation Required', 'Please type "CONFIRM" to proceed with redemption.');
            $('#confirmRedeem').focus();
            return false;
        }
        
        return true;
    });
    
    // Custom notification system
    function showNotification(type, title, message) {
        // Remove any existing notifications
        $('.custom-notification').remove();
        
        // Set icon and color based on type
        var icon, bgColor, borderColor;
        switch(type) {
            case 'success':
                icon = 'fa-check-circle';
                bgColor = '#d4edda';
                borderColor = '#c3e6cb';
                break;
            case 'error':
                icon = 'fa-times-circle';
                bgColor = '#f8d7da';
                borderColor = '#f5c6cb';
                break;
            case 'warning':
                icon = 'fa-exclamation-triangle';
                bgColor = '#fff3cd';
                borderColor = '#ffeeba';
                break;
            case 'info':
            default:
                icon = 'fa-info-circle';
                bgColor = '#d1ecf1';
                borderColor = '#bee5eb';
                break;
        }
        
        // Create notification HTML
        var notificationHtml = `
            <div class="custom-notification" style="display: none;">
                <div class="notification-icon">
                    <i class="fas ${icon}"></i>
                </div>
                <div class="notification-content">
                    <div class="notification-title">${title}</div>
                    <div class="notification-message">${message}</div>
                </div>
                <div class="notification-close">
                    <i class="fas fa-times"></i>
                </div>
            </div>
        `;
        
        // Append notification to body
        $('body').append(notificationHtml);
        
        // Set colors
        $('.custom-notification').css({
            'background-color': bgColor,
            'border-color': borderColor
        });
        
        // Show notification with animation
        $('.custom-notification').slideDown(300);
        
        // Auto-hide after 5 seconds
        setTimeout(function() {
            $('.custom-notification').slideUp(300, function() {
                $(this).remove();
            });
        }, 5000);
        
        // Close on click
        $('.notification-close').on('click', function() {
            $(this).parent().slideUp(300, function() {
                $(this).remove();
            });
        });
    }
});
