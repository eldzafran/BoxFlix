<?php
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Get user's current subscription
$stmt = $conn->prepare("
    SELECT s.*, i.amount, i.status as payment_status, i.payment_method 
    FROM subscriptions s
    LEFT JOIN invoices i ON s.id = i.subscription_id
    WHERE s.user_id = ? AND s.is_active = 1
    ORDER BY i.created_at DESC
    LIMIT 1
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$current_subscription = $stmt->get_result()->fetch_assoc();

// Get user's subscription history
$stmt = $conn->prepare("
    SELECT s.*, i.amount, i.status, i.created_at as payment_date, i.payment_method,
           CASE 
               WHEN s.plan = 'monthly' THEN 'Monthly Plan'
               WHEN s.plan = 'yearly' THEN 'Yearly Plan'
           END as plan_name,
           CASE 
               WHEN s.plan = 'monthly' THEN 30
               WHEN s.plan = 'yearly' THEN 365
           END as duration_days
    FROM subscriptions s
    JOIN invoices i ON s.id = i.subscription_id
    WHERE s.user_id = ?
    ORDER BY i.created_at DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$subscription_history = $stmt->get_result();

// Handle subscription form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['plan'])) {
        $plan = $_POST['plan'];
        $payment_method = $_POST['payment_method'] ?? 'dana';
        
        // Check if terms are accepted
        if (!isset($_POST['terms_accepted'])) {
            $error = 'You must accept BoxFlix terms and conditions to proceed.';
        } else {
            // Begin transaction
            $conn->begin_transaction();
            
            try {
                // Verify user exists
                $stmt = $conn->prepare("SELECT id FROM users WHERE id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                if ($stmt->get_result()->num_rows === 0) {
                    throw new Exception('User not found');
                }

                // Get current subscription if exists
                $stmt = $conn->prepare("SELECT * FROM subscriptions WHERE user_id = ? AND is_active = 1");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $current_sub = $stmt->get_result()->fetch_assoc();
                
                // Calculate subscription dates
                $start_date = date('Y-m-d H:i:s');
                if ($current_sub) {
                    // If there's an active subscription, extend from the end date
                    $end_date = $plan === 'monthly' 
                        ? date('Y-m-d H:i:s', strtotime($current_sub['end_date'] . ' +1 month'))
                        : date('Y-m-d H:i:s', strtotime($current_sub['end_date'] . ' +1 year'));
                } else {
                    // If no active subscription, start from now
                    $end_date = $plan === 'monthly' 
                        ? date('Y-m-d H:i:s', strtotime('+1 month'))
                        : date('Y-m-d H:i:s', strtotime('+1 year'));
                }
                
                // Calculate amount
                $amount = $plan === 'monthly' ? MONTHLY_PRICE : YEARLY_PRICE;
                
                if ($current_sub) {
                    // Update existing subscription
                    $stmt = $conn->prepare("UPDATE subscriptions SET end_date = ?, plan = ?, is_active = 1 WHERE id = ?");
                    $stmt->bind_param("ssi", $end_date, $plan, $current_sub['id']);
                    $stmt->execute();
                    $subscription_id = $current_sub['id'];
                } else {
                    // Create new subscription
                    $stmt = $conn->prepare("INSERT INTO subscriptions (user_id, plan, start_date, end_date, is_active) VALUES (?, ?, ?, ?, 1)");
                    $stmt->bind_param("isss", $user_id, $plan, $start_date, $end_date);
                    $stmt->execute();
                    $subscription_id = $conn->insert_id;
                }
                
                // Create invoice
                $stmt = $conn->prepare("INSERT INTO invoices (subscription_id, amount, payment_method, status) VALUES (?, ?, ?, 'completed')");
                $stmt->bind_param("ids", $subscription_id, $amount, $payment_method);
                $stmt->execute();
                
                $conn->commit();
                $success = $current_sub ? 'Subscription extended successfully!' : 'Subscription successful! You now have access to all premium movies.';
                
                // Refresh subscription data
                $stmt = $conn->prepare("
                    SELECT s.*, i.amount, i.status as payment_status, i.payment_method 
                    FROM subscriptions s
                    LEFT JOIN invoices i ON s.id = i.subscription_id
                    WHERE s.user_id = ? AND s.is_active = 1
                    ORDER BY i.created_at DESC
                    LIMIT 1
                ");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $current_subscription = $stmt->get_result()->fetch_assoc();
            } catch (Exception $e) {
                $conn->rollback();
                $error = 'Subscription failed. Please try again.';
            }
        }
    }
}

// Handle subscription cancellation
if (isset($_POST['cancel_subscription'])) {
    // Check if terms are accepted for cancellation
    if (!isset($_POST['cancel_terms_accepted'])) {
        $error = 'You must accept the cancellation terms to proceed.';
    } else {
        // Update subscription to inactive
        $stmt = $conn->prepare("UPDATE subscriptions SET is_active = 0 WHERE user_id = ? AND is_active = 1");
        $stmt->bind_param("i", $user_id);
        
        if ($stmt->execute()) {
            $success = 'Subscription cancelled successfully.';
        } else {
            $error = 'Failed to cancel subscription. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscription - BoxFlix</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="assets/bflixpng2.png">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Outfit', sans-serif;
        }
        body {
            background-color: #0f172a;
            color: #e2e8f0;
        }
        .navbar {
            background: rgba(15, 23, 42, 0.95);
            backdrop-filter: blur(10px);
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        .nav-left {
            display: flex;
            align-items: center;
            gap: 2rem;
        }
        .logo {
            color: #60a5fa;
            font-size: 1.8rem;
            font-weight: bold;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }
        .logo img {
            height: 70px;
            width: auto;
            object-fit: contain;
            transition: all 0.3s ease;
        }
        .logo:hover {
            color: #93c5fd;
            transform: scale(1.05);
        }
        .logo:hover img {
            transform: scale(1.05);
        }
        .logo i {
            font-size: 2rem;
        }
        .nav-links {
            display: flex;
            gap: 2rem;
            align-items: center;
        }
        .nav-links a {
            color: #e2e8f0;
            text-decoration: none;
            transition: all 0.3s ease;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            position: relative;
        }
        .nav-links a::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            width: 0;
            height: 2px;
            background: #60a5fa;
            transition: all 0.3s ease;
            transform: translateX(-50%);
        }
        .nav-links a:hover::after {
            width: 80%;
        }
        .nav-links a:hover {
            color: #60a5fa;
        }
        .user-profile {
            display: flex;
            align-items: center;
            gap: 1rem;
            background: rgba(255, 255, 255, 0.1);
            padding: 0.5rem 1rem;
            border-radius: 50px;
            transition: all 0.3s ease;
        }
        .user-profile:hover {
            background: rgba(255, 255, 255, 0.15);
        }
        .profile-avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #60a5fa 0%, #3b82f6 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.2rem;
            color: white;
            text-transform: uppercase;
            transition: transform 0.3s ease;
        }
        .profile-avatar:hover {
            transform: scale(1.1);
        }
        .username {
            color: #e2e8f0;
            font-weight: 500;
        }
        .main-content {
            margin-top: 80px;
            padding: 2rem;
        }
        .page-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 2rem;
            color: #ffffff;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .page-title i {
            color: #60a5fa;
        }
        .subscription-status {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 3rem;
            position: relative;
            overflow: hidden;
        }
        .subscription-status::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, #60a5fa, #3b82f6);
        }
        .status-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        .status-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #ffffff;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.9rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }
        .status-active {
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
            color: white;
        }
        .status-inactive {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
        }
        .status-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
        }
        .status-item {
            background: rgba(255, 255, 255, 0.05);
            padding: 1rem;
            border-radius: 10px;
        }
        .status-label {
            font-size: 0.9rem;
            color: #94a3b8;
            margin-bottom: 0.5rem;
        }
        .status-value {
            font-size: 1.2rem;
            font-weight: 600;
            color: #ffffff;
        }
        .subscription-plans {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }
        .plan-card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 15px;
            padding: 2rem;
            text-align: center;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        .plan-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }
        .plan-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, #60a5fa, #3b82f6);
        }
        .plan-name {
            font-size: 1.5rem;
            font-weight: 600;
            color: #ffffff;
            margin-bottom: 1rem;
        }
        .plan-price {
            font-size: 2.5rem;
            font-weight: 700;
            color: #60a5fa;
            margin-bottom: 1.5rem;
        }
        .plan-features {
            list-style: none;
            margin-bottom: 2rem;
            flex: 1;
        }
        .plan-features li {
            color: #94a3b8;
            margin-bottom: 0.8rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .plan-features li i {
            color: #60a5fa;
        }
        .subscribe-btn {
            display: inline-block;
            padding: 1rem 2rem;
            background: linear-gradient(135deg, #60a5fa 0%, #3b82f6 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
            margin-top: auto;
        }
        .subscribe-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(96, 165, 250, 0.3);
        }
        .history-section {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 15px;
            padding: 2rem;
        }
        .history-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #ffffff;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .history-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        .history-table th,
        .history-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        .history-table th {
            color: #94a3b8;
            font-weight: 500;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .history-table tr:last-child td {
            border-bottom: none;
        }
        .history-table tr {
            transition: all 0.3s ease;
        }
        .history-table tr:hover {
            background: rgba(255, 255, 255, 0.05);
        }
        .payment-status {
            padding: 0.4rem 0.8rem;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
        }
        .status-completed {
            background: rgba(34, 197, 94, 0.1);
            color: #22c55e;
        }
        .status-pending {
            background: rgba(234, 179, 8, 0.1);
            color: #eab308;
        }
        .status-failed {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
        }
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
        }

        .modal-content {
            background: rgba(15, 23, 42, 0.95);
            margin: 5% auto;
            padding: 2rem;
            border-radius: 15px;
            width: 90%;
            max-width: 500px;
            position: relative;
            animation: modalSlideIn 0.3s ease-out;
            max-height: 90vh;
            overflow-y: auto;
        }

        @keyframes modalSlideIn {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .close {
            position: absolute;
            right: 1.5rem;
            top: 1rem;
            font-size: 1.5rem;
            color: #94a3b8;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .close:hover {
            color: #e2e8f0;
        }

        .modal h2 {
            color: #ffffff;
            margin-bottom: 1.5rem;
            font-size: 1.5rem;
            text-align: center;
        }

        .payment-methods {
            display: grid;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .payment-method {
            background: rgba(255, 255, 255, 0.05);
            padding: 1rem;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .payment-method:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .payment-method input[type="radio"] {
            display: none;
        }

        .payment-method label {
            display: flex;
            align-items: center;
            gap: 1rem;
            color: #e2e8f0;
            cursor: pointer;
            font-size: 1.1rem;
        }

        .payment-method input[type="radio"]:checked + label {
            color: #60a5fa;
        }

        .payment-method i {
            font-size: 1.5rem;
        }

        .confirm-btn {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, #60a5fa 0%, #3b82f6 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .confirm-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(96, 165, 250, 0.3);
        }

        .payment-summary {
            background: rgba(255, 255, 255, 0.05);
            padding: 1.5rem;
            border-radius: 12px;
            margin: 1.5rem 0;
        }

        .payment-summary h3 {
            color: #60a5fa;
            margin-bottom: 1rem;
            font-size: 1.2rem;
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            color: #e2e8f0;
        }

        .summary-item.total {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            font-weight: bold;
            font-size: 1.1rem;
            color: #60a5fa;
        }

        .terms-checkbox {
            margin: 1.5rem 0;
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
        }

        .terms-checkbox input[type="checkbox"] {
            margin-top: 0.3rem;
        }

        .terms-checkbox label {
            color: #94a3b8;
            font-size: 0.9rem;
            line-height: 1.4;
        }

        .terms-checkbox a {
            color: #60a5fa;
            text-decoration: none;
        }

        .terms-checkbox a:hover {
            text-decoration: underline;
        }

        .cancel-subscription {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 15px;
            padding: 2rem;
            margin: 2rem 0;
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        .cancel-subscription h3 {
            color: #ef4444;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .cancel-subscription h3::before {
            content: '\f057';
            font-family: 'Font Awesome 5 Free';
            font-weight: 900;
        }

        .cancellation-terms {
            margin-top: 0.5rem;
            margin-left: 1.5rem;
            color: #94a3b8;
            font-size: 0.9rem;
            list-style-type: none;
        }

        .cancellation-terms li {
            margin-bottom: 0.5rem;
            position: relative;
            padding-left: 1.5rem;
        }

        .cancellation-terms li::before {
            content: '\f00c';
            font-family: 'Font Awesome 5 Free';
            font-weight: 900;
            color: #ef4444;
            position: absolute;
            left: 0;
            top: 0;
        }

        .cancel-btn {
            background: #ef4444;
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 1.5rem;
            transition: all 0.3s ease;
            font-size: 1rem;
            font-weight: 500;
        }

        .cancel-btn:hover {
            background: #dc2626;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(239, 68, 68, 0.3);
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-left">
            <a href="index.php" class="logo">
                <img src="assets/bflixpng2.png" alt="BoxFlix Logo">
            </a>
        </div>
        <div class="nav-links">
            <a href="wishlist.php">
                <i class="fas fa-heart"></i>
                Wishlist
            </a>
            <a href="subscription.php">
                <i class="fas fa-crown"></i>
                Subscription
            </a>
            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="user-profile">
                    <div class="profile-avatar">
                        <?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?>
                    </div>
                    <span class="username"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    <a href="logout.php" style="margin-left: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                </div>
            <?php else: ?>
                <a href="login.php">
                    <i class="fas fa-sign-in-alt"></i>
                    Login
                </a>
            <?php endif; ?>
        </div>
    </nav>

    <div class="main-content">
        <h1 class="page-title">
            <i class="fas fa-crown"></i>
            Subscription
        </h1>

        <div class="subscription-status">
            <div class="status-header">
                <h2 class="status-title">
                    <i class="fas fa-crown"></i>
                    Current Subscription
                </h2>
                <?php if ($current_subscription): ?>
                    <span class="status-badge status-active">
                        <i class="fas fa-check-circle"></i>
                        Active
                    </span>
                <?php else: ?>
                    <span class="status-badge status-inactive">
                        <i class="fas fa-times-circle"></i>
                        Not Subscribed
                    </span>
                <?php endif; ?>
            </div>
            <div class="status-details">
                <?php if ($current_subscription): ?>
                    <div class="status-item">
                        <div class="status-label">Time Left</div>
                        <div class="status-value">
                            <?php
                            $now = new DateTime();
                            $end = new DateTime($current_subscription['end_date']);
                            if ($end > $now) {
                                $interval = $now->diff($end);
                                if ($interval->y > 0) {
                                    echo $interval->y . ' year' . ($interval->y > 1 ? 's' : '');
                                    if ($interval->m > 0) echo ' ' . $interval->m . ' month' . ($interval->m > 1 ? 's' : '');
                                    if ($interval->d > 0) echo ' ' . $interval->d . ' day' . ($interval->d > 1 ? 's' : '');
                                } elseif ($interval->m > 0) {
                                    echo $interval->m . ' month' . ($interval->m > 1 ? 's' : '');
                                    if ($interval->d > 0) echo ' ' . $interval->d . ' day' . ($interval->d > 1 ? 's' : '');
                                } else {
                                    echo $interval->d . ' day' . ($interval->d > 1 ? 's' : '');
                                }
                                echo ' left';
                            } else {
                                echo 'Expired';
                            }
                            ?>
                        </div>
                    </div>
                    <div class="status-item">
                        <div class="status-label">Start Date</div>
                        <div class="status-value"><?php echo date('d M Y', strtotime($current_subscription['start_date'])); ?></div>
                    </div>
                    <div class="status-item">
                        <div class="status-label">End Date</div>
                        <div class="status-value"><?php echo date('d M Y', strtotime($current_subscription['end_date'])); ?></div>
                    </div>
                <?php else: ?>
                    <div class="status-item">
                        <div class="status-label">Status</div>
                        <div class="status-value">No Active Subscription</div>
                    </div>
                    <div class="status-item">
                        <div class="status-label">Benefits</div>
                        <div class="status-value">Subscribe to access premium content</div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($current_subscription): ?>
        <div class="cancel-subscription">
            <h3>Cancel Subscription</h3>
            <form method="POST" action="" onsubmit="return confirmCancellation()">
                <div class="terms-checkbox">
                    <input type="checkbox" id="cancel_terms_accepted" name="cancel_terms_accepted" required>
                    <label for="cancel_terms_accepted">
                        I understand that by cancelling:
                        <ul class="cancellation-terms">
                            <li>My subscription will not auto-renew</li>
                            <li>I will lose access to premium content</li>
                            <li>I can resubscribe at any time</li>
                        </ul>
                    </label>
                </div>
                <button type="submit" name="cancel_subscription" class="cancel-btn">
                    <i class="fas fa-times-circle"></i>
                    Cancel Subscription
                </button>
            </form>
        </div>
        <?php endif; ?>

        <div class="subscription-plans">
            <div class="plan-card">
                <h3 class="plan-name">Monthly Plan</h3>
                <div class="plan-price">Rp <?php echo number_format(MONTHLY_PRICE, 0, ',', '.'); ?></div>
                <ul class="plan-features">
                    <li><i class="fas fa-check"></i> Access to all premium movies</li>
                    <li><i class="fas fa-check"></i> HD quality streaming</li>
                    <li><i class="fas fa-check"></i> No ads</li>
                    <li><i class="fas fa-check"></i> 30 days validity</li>
                </ul>
                <button class="subscribe-btn" onclick="showPaymentModal('monthly')">Subscribe Now</button>
            </div>
            <div class="plan-card">
                <h3 class="plan-name">Yearly Plan</h3>
                <div class="plan-price">Rp <?php echo number_format(YEARLY_PRICE, 0, ',', '.'); ?></div>
                <ul class="plan-features">
                    <li><i class="fas fa-check"></i> Access to all premium movies</li>
                    <li><i class="fas fa-check"></i> HD quality streaming</li>
                    <li><i class="fas fa-check"></i> No ads</li>
                    <li><i class="fas fa-check"></i> 365 days validity</li>
                    <li><i class="fas fa-check"></i> Save 17% compared to monthly</li>
                </ul>
                <button class="subscribe-btn" onclick="showPaymentModal('yearly')">Subscribe Now</button>
            </div>
        </div>

        <!-- Payment Modal -->
        <div id="paymentModal" class="modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h2>Select Payment Method</h2>
                <form id="paymentForm" method="POST">
                    <input type="hidden" name="plan" id="selectedPlan">
                    <div class="payment-methods">
                        <div class="payment-method">
                            <input type="radio" name="payment_method" id="dana" value="dana" checked>
                            <label for="dana">
                                <i class="fas fa-wallet"></i>
                                DANA
                            </label>
                        </div>
                        <div class="payment-method">
                            <input type="radio" name="payment_method" id="gopay" value="gopay">
                            <label for="gopay">
                                <i class="fas fa-mobile-alt"></i>
                                GoPay
                            </label>
                        </div>
                    </div>
                    <div class="payment-summary">
                        <h3>Payment Summary</h3>
                        <div class="summary-item">
                            <span>Selected Plan:</span>
                            <span id="selected-plan">Monthly</span>
                        </div>
                        <div class="summary-item">
                            <span>Duration:</span>
                            <span id="plan-duration">1 Month</span>
                        </div>
                        <div class="summary-item total">
                            <span>Total Amount:</span>
                            <span id="total-amount">Rp <?php echo number_format(MONTHLY_PRICE, 0, ',', '.'); ?></span>
                        </div>
                    </div>

                    <div class="terms-checkbox">
                        <input type="checkbox" id="terms_accepted" name="terms_accepted" required>
                        <label for="terms_accepted">
                            I agree to the <a href="#" onclick="showTerms()">BoxFlix Terms and Conditions</a> and understand that this subscription will automatically renew unless cancelled.
                        </label>
                    </div>

                    <button type="submit" class="confirm-btn">Confirm Payment</button>
                </form>
            </div>
        </div>

        <div class="history-section">
            <h2 class="history-title">
                <i class="fas fa-history"></i>
                Subscription History
            </h2>
            <table class="history-table">
                <thead>
                    <tr>
                        <th>Plan</th>
                        <th>Duration</th>
                        <th>Amount</th>
                        <th>Payment Method</th>
                        <th>Status</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Payment Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($history = $subscription_history->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $history['plan_name']; ?></td>
                            <td><?php echo $history['duration_days']; ?> days</td>
                            <td>Rp <?php echo number_format($history['amount'], 0, ',', '.'); ?></td>
                            <td>
                                <span class="payment-method">
                                    <i class="fas fa-<?php echo $history['payment_method'] === 'dana' ? 'wallet' : 'mobile-alt'; ?>"></i>
                                    <?php echo strtoupper($history['payment_method']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="payment-status status-<?php echo $history['status']; ?>">
                                    <i class="fas fa-circle"></i>
                                    <?php echo ucfirst($history['status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('d M Y', strtotime($history['start_date'])); ?></td>
                            <td><?php echo date('d M Y', strtotime($history['end_date'])); ?></td>
                            <td><?php echo date('d M Y H:i', strtotime($history['payment_date'])); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // Get modal elements
        const modal = document.getElementById('paymentModal');
        const closeBtn = document.getElementsByClassName('close')[0];
        const paymentForm = document.getElementById('paymentForm');
        const selectedPlanInput = document.getElementById('selectedPlan');

        // Show modal function
        function showPaymentModal(plan) {
            selectedPlanInput.value = plan;
            modal.style.display = 'block';
        }

        // Close modal when clicking the X
        closeBtn.onclick = function() {
            modal.style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }

        // Handle form submission
        paymentForm.onsubmit = function(e) {
            e.preventDefault();
            const formData = new FormData(paymentForm);
            fetch('subscription.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(html => {
                document.documentElement.innerHTML = html;
            })
            .catch(error => {
                console.error('Error:', error);
            });
        }

        // Add loading animation for plan cards
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        });

        document.querySelectorAll('.plan-card').forEach(card => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            card.style.transition = 'all 0.5s ease';
            observer.observe(card);
        });

        function updatePaymentSummary() {
            const plan = document.querySelector('input[name="plan"]:checked').value;
            const selectedPlan = document.getElementById('selected-plan');
            const planDuration = document.getElementById('plan-duration');
            const totalAmount = document.getElementById('total-amount');
            
            if (plan === 'monthly') {
                selectedPlan.textContent = 'Monthly';
                planDuration.textContent = '1 Month';
                totalAmount.textContent = 'Rp <?php echo number_format(MONTHLY_PRICE, 0, ',', '.'); ?>';
            } else {
                selectedPlan.textContent = 'Yearly';
                planDuration.textContent = '12 Months';
                totalAmount.textContent = 'Rp <?php echo number_format(YEARLY_PRICE, 0, ',', '.'); ?>';
            }
        }

        // Add event listeners to plan radio buttons
        document.querySelectorAll('input[name="plan"]').forEach(radio => {
            radio.addEventListener('change', updatePaymentSummary);
        });

        function showTerms() {
            alert('BoxFlix Terms and Conditions:\n\n1. Subscription will automatically renew at the end of each billing period.\n2. You can cancel your subscription at any time.\n3. No refunds will be provided for unused portions of the subscription.\n4. BoxFlix reserves the right to modify subscription terms and pricing.\n5. All content is subject to BoxFlix\'s content guidelines and policies.');
        }

        function confirmCancellation() {
            if (!document.getElementById('cancel_terms_accepted').checked) {
                alert('Please read and accept the cancellation terms first.');
                return false;
            }
            return confirm('Are you sure you want to cancel your subscription?');
        }
    </script>
</body>
</html> 