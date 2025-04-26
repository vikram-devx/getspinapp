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

// Get some rewards for showcase
$db = Database::getInstance();
$conn = $db->getConnection();
$query = "SELECT * FROM rewards WHERE is_active = 1 ORDER BY points_required ASC LIMIT 3";
$result = $db->query($query);

$showcase_rewards = [];
if ($result) {
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        $showcase_rewards[] = $row;
    }
}

include 'includes/header.php';
?>

<!-- Hero Section -->
<div class="hero-section text-center">
    <div class="container">
        <?php if (!empty($app_logo)): ?>
        <div class="mb-4">
            <img src="<?php echo htmlspecialchars($app_logo); ?>" alt="<?php echo htmlspecialchars($app_name); ?>" class="img-fluid" style="max-height: 80px;">
        </div>
        <?php endif; ?>
        <h1 class="display-4">Earn Rewards for Completing Simple Tasks</h1>
        <p class="lead">Install apps, take surveys, and earn points that you can redeem for gift cards and other rewards.</p>
        <div class="mt-4">
            <a href="register.php" class="btn btn-light btn-lg me-2">Create an Account</a>
            <a href="login.php" class="btn btn-outline-light btn-lg">Login</a>
        </div>
    </div>
</div>

<!-- How It Works Section -->
<section class="how-it-works">
    <div class="container">
        <h2 class="text-center mb-5">How It Works</h2>
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card h-100 step-card">
                    <div class="step-icon">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <h3>1. Create an Account</h3>
                    <p>Sign up for free and set up your profile to get started.</p>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card h-100 step-card">
                    <div class="step-icon">
                        <i class="fas fa-tasks"></i>
                    </div>
                    <h3>2. Complete Tasks</h3>
                    <p>Browse available tasks like app installs and surveys, complete them to earn points.</p>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card h-100 step-card">
                    <div class="step-icon">
                        <i class="fas fa-gift"></i>
                    </div>
                    <h3>3. Redeem Rewards</h3>
                    <p>Use your points to claim gift cards, PayPal cash, and other rewards.</p>
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
                    <p class="testimonial-content">"I've earned enough points to get several Amazon gift cards. The tasks are easy and the rewards come quickly!"</p>
                    <p class="testimonial-author">- Sarah J.</p>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="testimonial-card h-100">
                    <p class="testimonial-content">"This is the best rewards app I've used. The point system is fair and there are always new tasks available."</p>
                    <p class="testimonial-author">- Michael T.</p>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="testimonial-card h-100">
                    <p class="testimonial-content">"I've been using this app for 3 months and already redeemed over $50 in PayPal cash. Highly recommended!"</p>
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
                        <h4>Multiple Reward Options</h4>
                        <p>Choose from various gift cards, PayPal cash, and other rewards.</p>
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
