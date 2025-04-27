/**
 * EmailJS Configuration
 * This script handles email notifications using EmailJS service
 */

// Initialize EmailJS with your User ID
(function() {
    // Check if EmailJS User ID is available
    if (window.EMAILJS_USER_ID) {
        try {
            emailjs.init(window.EMAILJS_USER_ID);
            console.log('EmailJS initialized successfully');
        } catch (e) {
            console.error('Error initializing EmailJS:', e);
        }
    } else {
        console.error('EmailJS User ID not set');
    }
})();

/**
 * Send redemption notification email to admin
 * @param {Object} redemptionData - The redemption data
 * @returns {Promise} - Promise with the result
 */
function sendRedemptionEmail(redemptionData) {
    // Check if all required EmailJS variables are set
    if (!window.EMAILJS_USER_ID || !window.EMAILJS_SERVICE_ID || !window.EMAILJS_TEMPLATE_ID) {
        console.error('EmailJS configuration incomplete');
        return Promise.reject('EmailJS not properly configured');
    }

    // Define template parameters
    const templateParams = {
        to_email: redemptionData.adminEmail,
        user_name: redemptionData.username,
        user_id: redemptionData.userId,
        reward_name: redemptionData.rewardName,
        points_used: redemptionData.pointsUsed,
        redemption_id: redemptionData.redemptionId,
        redemption_details: redemptionData.redemptionDetails || 'None',
        date_time: new Date().toLocaleString()
    };

    // Send the email
    return emailjs.send(
        window.EMAILJS_SERVICE_ID,
        window.EMAILJS_TEMPLATE_ID,
        templateParams
    ).then(function(response) {
        console.log('Email sent successfully:', response);
        return response;
    }).catch(function(error) {
        console.error('Email sending failed:', error);
        throw error;
    });
}

/**
 * Show notification toast
 * @param {string} type - success, error, warning, info
 * @param {string} message - Notification message
 */
function showNotificationToast(type, message) {
    // Create toast container if it doesn't exist
    let toastContainer = document.getElementById('toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'toast-container';
        toastContainer.className = 'position-fixed bottom-0 end-0 p-3';
        document.body.appendChild(toastContainer);
    }

    // Create toast element
    const toastId = 'toast-' + Date.now();
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white bg-${type} border-0`;
    toast.id = toastId;
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-atomic', 'true');
    
    // Set toast content
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    `;
    
    // Add toast to container
    toastContainer.appendChild(toast);
    
    // Initialize Bootstrap toast
    const bsToast = new bootstrap.Toast(toast, {
        autohide: true,
        delay: 5000
    });
    
    // Show toast
    bsToast.show();
    
    // Remove toast after it's hidden
    toast.addEventListener('hidden.bs.toast', function() {
        toast.remove();
    });
}