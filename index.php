<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$auth = new Auth();

// If user is already logged in, redirect to dashboard
if ($auth->isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

// Get some rewards for showcase - focusing on CoinMaster and Monopoly only (rewards_id 6 and 7)
$db = Database::getInstance();
$conn = $db->getConnection();
$query = "SELECT * FROM rewards WHERE is_active = 1 AND (id = 6 OR id = 7 OR name LIKE '%Coin Master%' OR name LIKE '%Monopoly%') ORDER BY points_required ASC LIMIT 3";
$result = $db->query($query);

$showcase_rewards = [];
if ($result) {
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        $showcase_rewards[] = $row;
    }
}

// Get top users for leaderboard (top 5)
$leaderboard_query = "SELECT id, username, points, 
    (SELECT COUNT(*) FROM users u2 WHERE u2.points > u1.points) + 1 AS rank 
    FROM users u1 
    WHERE is_admin = 0 
    ORDER BY points DESC 
    LIMIT 5";
$leaderboard_result = $conn->query($leaderboard_query);
$top_users = [];
if ($leaderboard_result) {
    while ($row = $leaderboard_result->fetch(PDO::FETCH_ASSOC)) {
        $top_users[] = $row;
    }
}

// Get recent successful redemptions (focus on game rewards)
$recent_redemptions_query = "SELECT r.name, rd.created_at, rd.redemption_details, u.username
                           FROM redemptions rd
                           JOIN rewards r ON rd.reward_id = r.id
                           JOIN users u ON rd.user_id = u.id
                           WHERE rd.status = 'completed' 
                           AND (r.id = 6 OR r.id = 7 OR r.name LIKE '%Coin Master%' OR r.name LIKE '%Monopoly%')
                           ORDER BY rd.created_at DESC
                           LIMIT 5";
$recent_result = $conn->query($recent_redemptions_query);
$recent_redemptions = [];
if ($recent_result) {
    while ($row = $recent_result->fetch(PDO::FETCH_ASSOC)) {
        $recent_redemptions[] = $row;
    }
}

include 'includes/header.php';
?>

<!-- Promo Slider Section -->
<div class="hero-section">
    <div class="promo-slider">
        <div class="promo-slides">
            <?php
            // Get all active promo slides
            $stmt = $db->prepare("SELECT * FROM promo_slides WHERE active = 1 ORDER BY display_order ASC");
            $stmt->execute();
            $promoSlides = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // If no slides exist, create a default one
            if (empty($promoSlides)) {
                $promoSlides = [
                    [
                        'id' => 1,
                        'title' => 'Welcome to ' . htmlspecialchars($app_name),
                        'description' => 'Earn Rewards for Completing Simple Tasks. Install apps, take surveys, and earn points that you can redeem for gift cards and other rewards.',
                        'button_text' => 'Get Started',
                        'button_url' => 'register.php',
                        'image_path' => 'assets/img/promo-default.jpg'
                    ]
                ];
            }
            
            // Display all slides
            foreach ($promoSlides as $index => $slide):
                $bgImage = !empty($slide['image_path']) ? $slide['image_path'] : 'assets/img/promo-default.jpg';
            ?>
            <div class="promo-slide" style="background-image: url('<?php echo htmlspecialchars($bgImage); ?>');">
                <?php if (!empty($slide['title']) || !empty($slide['description']) || !empty($slide['button_text'])): ?>
                <div class="slide-content">
                    <?php if (!empty($slide['title'])): ?>
                    <h1 class="display-4"><?php echo htmlspecialchars($slide['title']); ?></h1>
                    <?php endif; ?>
                    
                    <?php if (!empty($slide['description'])): ?>
                    <p class="lead"><?php echo htmlspecialchars($slide['description']); ?></p>
                    <?php endif; ?>
                    
                    <?php if (!empty($slide['button_text']) && !empty($slide['button_url'])): ?>
                    <div class="mt-4">
                        <a href="<?php echo htmlspecialchars($slide['button_url']); ?>" class="btn btn-light btn-lg"><?php echo htmlspecialchars($slide['button_text']); ?></a>
                        <?php if ($index === 0): ?>
                        <a href="login.php" class="btn btn-outline-light btn-lg ms-2">Login</a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Slider controls -->
        <?php if (count($promoSlides) > 1): ?>
        <div class="slider-controls">
            <?php for ($i = 0; $i < count($promoSlides); $i++): ?>
            <div class="slider-dot <?php echo ($i === 0) ? 'active' : ''; ?>" data-index="<?php echo $i; ?>"></div>
            <?php endfor; ?>
        </div>
        
        <div class="slider-arrow slider-arrow-left">
            <i class="fas fa-chevron-left"></i>
        </div>
        <div class="slider-arrow slider-arrow-right">
            <i class="fas fa-chevron-right"></i>
        </div>
        <?php endif; ?>
    </div>
</div>

</div> <!-- Close full-width-container -->

<!-- How It Works Section -->
<section class="how-it-works" style="margin-top: 0; padding-top: 2rem;">
    <div class="container">
        <h2 class="text-center mb-5">How It Works</h2>
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card h-100 step-card">
                    <div class="step-icon">
                        <i class="fas fa-user-plus text-primary"></i>
                    </div>
                    <h3>1. Create an Account</h3>
                    <p>Sign up for free and set up your profile to get started.</p>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card h-100 step-card">
                    <div class="step-icon">
                        <i class="fas fa-tasks text-primary"></i>
                    </div>
                    <h3>2. Complete Tasks</h3>
                    <p>Browse available tasks like app installs and surveys, complete them to earn points.</p>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card h-100 step-card">
                    <div class="step-icon">
                        <i class="fas fa-gift text-primary"></i>
                    </div>
                    <h3>3. Redeem Rewards</h3>
                    <p>Use your points to claim Coin Master spins, Monopoly Go dice rolls, and other rewards.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Leaderboard & Recent Claims Section -->
<section class="leaderboard-recent-claims py-5 bg-light">
    <div class="container">
        <div class="row">
            <!-- Leaderboard Column -->
            <div class="col-lg-6 mb-4">
                <div class="card h-100">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h3 class="mb-0 h5">Top Earners Leaderboard</h3>
                        <a href="register.php" class="btn btn-sm btn-light">Join Now</a>
                    </div>
                    <div class="card-body p-0">
                        <?php if (count($top_users) > 0): ?>
                        <div class="top-users-list">
                            <?php foreach ($top_users as $index => $user): ?>
                                <div class="leaderboard-item d-flex justify-content-between align-items-center p-3" style="border-bottom: 1px solid #eee;">
                                    <div class="d-flex align-items-center">
                                        <div class="rank-number me-3">
                                            <?php if ($user['rank'] <= 3): ?>
                                                <?php if ($user['rank'] == 1): ?>
                                                    <i class="fas fa-trophy text-warning" style="font-size: 1.5rem;"></i>
                                                <?php elseif ($user['rank'] == 2): ?>
                                                    <i class="fas fa-medal" style="color: #C0C0C0; font-size: 1.25rem;"></i>
                                                <?php else: ?>
                                                    <i class="fas fa-medal" style="color: #CD7F32; font-size: 1.25rem;"></i>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="fw-bold">#<?php echo $user['rank']; ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="user-avatar-mini me-3 bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                            <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                                        </div>
                                        <div class="username">
                                            <span class="fw-bold"><?php echo htmlspecialchars($user['username']); ?></span>
                                        </div>
                                    </div>
                                    <div class="points">
                                        <strong><?php echo formatPoints($user['points']); ?></strong> points
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                        <div class="p-4 text-center">
                            <p class="text-muted mb-0">No users on leaderboard yet</p>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer text-center">
                        <p class="mb-0">Want to be on the leaderboard? <a href="register.php" class="fw-bold">Sign up now</a></p>
                    </div>
                </div>
            </div>
            
            <!-- Recent Claims Column -->
            <div class="col-lg-6 mb-4">
                <div class="card h-100">
                    <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                        <h3 class="mb-0 h5">Recent Rewards Claimed</h3>
                        <a href="register.php" class="btn btn-sm btn-light">Get Yours</a>
                    </div>
                    <div class="card-body p-0">
                        <?php if (count($recent_redemptions) > 0): ?>
                        <div class="recent-claims-list">
                            <?php foreach ($recent_redemptions as $redemption): 
                                $details = !empty($redemption['redemption_details']) ? json_decode($redemption['redemption_details'], true) : null;
                                $spins = ($details && isset($details['spins'])) ? $details['spins'] : '';
                            ?>
                                <div class="claim-item d-flex justify-content-between align-items-center p-3" style="border-bottom: 1px solid #eee;">
                                    <div class="d-flex align-items-center">
                                        <div class="reward-icon me-3">
                                            <?php if (stripos($redemption['name'], 'coin master') !== false): ?>
                                                <i class="fas fa-sync-alt text-warning" style="font-size: 1.25rem;"></i>
                                            <?php elseif (stripos($redemption['name'], 'monopoly') !== false): ?>
                                                <i class="fas fa-dice text-info" style="font-size: 1.25rem;"></i>
                                            <?php else: ?>
                                                <i class="fas fa-gift text-success" style="font-size: 1.25rem;"></i>
                                            <?php endif; ?>
                                        </div>
                                        <div class="claim-details">
                                            <div class="fw-bold"><?php echo htmlspecialchars($redemption['name']); ?></div>
                                            <div class="small text-muted">
                                                <?php if (!empty($spins)): ?>
                                                <?php echo htmlspecialchars($spins); ?> spins
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="claim-meta text-end">
                                        <div class="small text-truncate" style="max-width: 100px;">
                                            <?php echo htmlspecialchars($redemption['username']); ?>
                                        </div>
                                        <div class="small text-muted">
                                            <?php echo date('M d', strtotime($redemption['created_at'])); ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                        <div class="p-4 text-center">
                            <p class="text-muted mb-0">No recent claims yet</p>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer text-center">
                        <p class="mb-0">Ready to claim rewards? <a href="register.php" class="fw-bold">Join now</a></p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Referral Bonus CTA -->
        <div class="row mt-3">
            <div class="col-12">
                <div class="card bg-primary text-white">
                    <div class="card-body p-4">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h3>Earn 100 Bonus Points!</h3>
                                <p class="mb-md-0">Invite your friends and get 100 bonus points for each friend who joins and completes a task. The more friends you invite, the more rewards you can claim!</p>
                            </div>
                            <div class="col-md-4 text-md-end">
                                <a href="register.php" class="btn btn-light btn-lg">Join & Start Inviting</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Rewards Showcase -->
<section class="rewards-showcase">
    <div class="container">
        <h2 class="text-center mb-5">Popular Rewards</h2>
        <div class="row">
            <?php foreach ($showcase_rewards as $reward): ?>
            <div class="col-md-4 mb-4">
                <div class="card h-100 text-center">
                    <div class="card-body">
                        <div class="mb-3">
                            <i class="fas fa-gift fa-3x text-primary"></i>
                        </div>
                        <h4><?php echo htmlspecialchars($reward['name']); ?></h4>
                        <p class="reward-points"><?php echo formatPoints($reward['points_required']); ?> Points</p>
                        <p><?php echo htmlspecialchars($reward['description']); ?></p>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-4">
            <a href="register.php" class="btn btn-primary btn-lg">Start Earning Now</a>
        </div>
    </div>
</section>

<!-- Testimonials Section -->
<section class="testimonials py-5">
    <div class="container">
        <h2 class="text-center mb-5">What Our Users Say</h2>
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="testimonial-card h-100">
                    <p class="testimonial-content">"I've earned enough points to get 500 Coin Master spins this month. The tasks are easy and the rewards come quickly!"</p>
                    <p class="testimonial-author">- Sarah J.</p>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="testimonial-card h-100">
                    <p class="testimonial-content">"This is the best app for getting free Monopoly Go dice rolls. The point system is fair and there are always new tasks available."</p>
                    <p class="testimonial-author">- Michael T.</p>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="testimonial-card h-100">
                    <p class="testimonial-content">"I've been using this app for 3 months and already redeemed over 2,000 Coin Master spins and 1,500 Monopoly Go dice rolls. Highly recommended!"</p>
                    <p class="testimonial-author">- Lisa R.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="features py-5 bg-light">
    <div class="container">
        <h2 class="text-center mb-5">Our Features</h2>
        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="d-flex">
                    <div class="me-3">
                        <i class="fas fa-check-circle fa-2x text-primary"></i>
                    </div>
                    <div>
                        <h4>Wide Variety of Tasks</h4>
                        <p>From app installs to surveys, we offer many ways to earn points.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-4">
                <div class="d-flex">
                    <div class="me-3">
                        <i class="fas fa-check-circle fa-2x text-primary"></i>
                    </div>
                    <div>
                        <h4>Instant Tracking</h4>
                        <p>Your completed tasks are tracked instantly and points are credited to your account.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-4">
                <div class="d-flex">
                    <div class="me-3">
                        <i class="fas fa-check-circle fa-2x text-primary"></i>
                    </div>
                    <div>
                        <h4>Game Rewards</h4>
                        <p>Get free Coin Master spins and Monopoly Go dice rolls quickly and reliably.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-4">
                <div class="d-flex">
                    <div class="me-3">
                        <i class="fas fa-check-circle fa-2x text-primary"></i>
                    </div>
                    <div>
                        <h4>Fast Reward Processing</h4>
                        <p>Redeem your points and receive your rewards quickly.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="cta py-5">
    <div class="container">
        <div class="row">
            <div class="col-lg-8 offset-lg-2">
                <div class="card">
                    <div class="card-body text-center py-5">
                        <h2 class="mb-3">Ready to Start Earning?</h2>
                        <p class="lead mb-4">Join thousands of users who are already earning rewards for completing simple tasks.</p>
                        <a href="register.php" class="btn btn-primary btn-lg">Sign Up Now</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
