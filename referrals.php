<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$auth = new Auth();

// Check if user is logged in
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$current_user = $auth->getUser();
$referral_stats = $auth->getReferralStats($current_user['id']);

// Generate referral link
$referral_link = APP_URL . '/register.php?ref=' . $current_user['referral_code'];

include 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <h2 class="mobile-title">Refer Friends & Earn Rewards</h2>
                <p class="lead mobile-text">Invite friends to join GetSpins App and earn <strong>100 points</strong> for each friend who registers!</p>
                
                <hr>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="card bg-light mb-4">
                            <div class="card-body">
                                <h4 class="mobile-subtitle">Your Referral Code</h4>
                                <div class="d-flex align-items-center mb-3 mobile-referral-code-container">
                                    <div class="referral-code-display p-3 bg-white border rounded me-3">
                                        <span class="h3 mb-0 font-monospace mobile-ref-code"><?php echo htmlspecialchars($current_user['referral_code']); ?></span>
                                    </div>
                                    <button class="btn btn-outline-primary copy-btn" data-clipboard-text="<?php echo htmlspecialchars($current_user['referral_code']); ?>">
                                        <i class="fas fa-copy"></i> <span class="copy-text">Copy</span>
                                    </button>
                                </div>
                                
                                <h4 class="mobile-subtitle">Your Referral Link</h4>
                                <div class="input-group mb-3">
                                    <input type="text" class="form-control mobile-input" value="<?php echo htmlspecialchars($referral_link); ?>" id="referral-link" readonly>
                                    <button class="btn btn-outline-primary copy-btn" type="button" data-clipboard-target="#referral-link">
                                        <i class="fas fa-copy"></i> <span class="copy-text">Copy</span>
                                    </button>
                                </div>
                                
                                <div class="social-share mt-4">
                                    <p class="mobile-text">Share your referral link:</p>
                                    <div class="share-buttons">
                                        <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode($referral_link); ?>" target="_blank" class="btn btn-primary share-btn">
                                            <i class="fab fa-facebook-f"></i>
                                        </a>
                                        <a href="https://twitter.com/intent/tweet?text=<?php echo urlencode('Join me on GetSpins App and earn free Monopoly Go and Coin Master rewards! Sign up using my referral code: ' . $current_user['referral_code']); ?>&url=<?php echo urlencode($referral_link); ?>" target="_blank" class="btn btn-info share-btn">
                                            <i class="fab fa-twitter"></i>
                                        </a>
                                        <a href="https://api.whatsapp.com/send?text=<?php echo urlencode('Join me on GetSpins App and earn free Monopoly Go and Coin Master rewards! Sign up using my referral code: ' . $current_user['referral_code'] . ' or click this link: ' . $referral_link); ?>" target="_blank" class="btn btn-success share-btn">
                                            <i class="fab fa-whatsapp"></i>
                                        </a>
                                        <a href="mailto:?subject=<?php echo urlencode('Join me on GetSpins App!'); ?>&body=<?php echo urlencode('Hey!\n\nI thought you might want to check out GetSpins App. You can earn free Monopoly Go and Coin Master rewards!\n\nUse my referral code when you sign up: ' . $current_user['referral_code'] . '\n\nOr click this link: ' . $referral_link . '\n\nThanks!'); ?>" class="btn btn-secondary share-btn">
                                            <i class="fas fa-envelope"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h4 class="mobile-subtitle">How It Works</h4>
                                <ol class="mt-3 mobile-list">
                                    <li>Share your unique referral code or link with friends</li>
                                    <li>When they sign up using your code, they're linked to your account</li>
                                    <li>You'll earn <strong>100 points</strong> for each successful referral</li>
                                    <li>Use your points to redeem Monopoly Go or Coin Master rewards!</li>
                                </ol>
                                
                                <div class="alert alert-info mobile-alert">
                                    <i class="fas fa-info-circle"></i> The more friends you refer, the more free spins you'll earn!
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3 class="mobile-title">Your Referral Stats</h3>
            </div>
            <div class="card-body">
                <div class="row text-center stats-container">
                    <div class="col-4">
                        <div class="stat-item p-2 p-md-3">
                            <h4 class="text-primary mb-1 mobile-stat-number"><?php echo number_format($referral_stats['total_referrals']); ?></h4>
                            <p class="text-muted mb-0 mobile-stat-label">Total Referrals</p>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="stat-item p-2 p-md-3">
                            <h4 class="text-success mb-1 mobile-stat-number"><?php echo number_format($referral_stats['successful_referrals']); ?></h4>
                            <p class="text-muted mb-0 mobile-stat-label">Successful Refs</p>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="stat-item p-2 p-md-3">
                            <h4 class="text-info mb-1 mobile-stat-number"><?php echo formatPoints($referral_stats['total_earned']); ?></h4>
                            <p class="text-muted mb-0 mobile-stat-label">Points Earned</p>
                        </div>
                    </div>
                </div>
                
                <?php if (!empty($referral_stats['recent_referrals'])): ?>
                <div class="mt-4">
                    <h4 class="mobile-subtitle">Recent Referrals</h4>
                    <div class="table-responsive mobile-table-container">
                        <table class="table table-hover mobile-table">
                            <thead>
                                <tr>
                                    <th class="mobile-th">Username</th>
                                    <th class="mobile-th">Date</th>
                                    <th class="mobile-th">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($referral_stats['recent_referrals'] as $referral): ?>
                                <tr>
                                    <td class="mobile-td"><?php echo htmlspecialchars($referral['username']); ?></td>
                                    <td class="mobile-td"><?php echo formatDate($referral['created_at']); ?></td>
                                    <td class="mobile-td">
                                        <?php if ($referral['status'] === 'completed'): ?>
                                            <span class="badge bg-success mobile-badge">Completed</span>
                                        <?php elseif ($referral['status'] === 'pending'): ?>
                                            <span class="badge bg-warning mobile-badge">Pending</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary mobile-badge"><?php echo htmlspecialchars($referral['status']); ?></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php else: ?>
                <div class="alert alert-light mt-4 mobile-alert">
                    <p class="mb-0 mobile-text">You haven't referred anyone yet. Share your referral code to start earning rewards!</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Add ClipboardJS for copy functionality -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/2.0.8/clipboard.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize clipboard.js
    var clipboard = new ClipboardJS('.copy-btn');
    
    clipboard.on('success', function(e) {
        // Show success notification
        showNotification('success', 'Copied!', 'Copied to clipboard successfully.');
        e.clearSelection();
    });
});
</script>

<?php include 'includes/dashboard_footer.php'; ?>