$(document).ready(function() {
    
    // Initialize Bootstrap tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
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
        
        // If we have a direct offer link from OGAds, use it
        if (taskLink) {
            // Set the form's action to tasks.php for proper tracking
            $('#taskForm').attr('action', 'tasks.php');
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
    
    // Loading indicator for task start
    $('.start-task-btn').on('click', function() {
        $(this).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Loading...');
        $(this).prop('disabled', true);
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
