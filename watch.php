<?php
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Check if movie ID is provided
if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit();
}

$movie_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

// Get movie details
$stmt = $conn->prepare("SELECT * FROM films WHERE id = ?");
$stmt->bind_param("i", $movie_id);
$stmt->execute();
$movie = $stmt->get_result()->fetch_assoc();

if (!$movie) {
    header('Location: index.php');
    exit();
}

// Check if user has subscription for premium movies
$has_subscription = false;
if ($movie['is_premium']) {
    $stmt = $conn->prepare("SELECT * FROM subscriptions WHERE user_id = ? AND is_active = 1 AND end_date > NOW()");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $has_subscription = $stmt->get_result()->num_rows > 0;
}

// Check if movie is in wishlist
$stmt = $conn->prepare("SELECT id FROM wishlist WHERE user_id = ? AND film_id = ?");
$stmt->bind_param("ii", $user_id, $movie_id);
$stmt->execute();
$in_wishlist = $stmt->get_result()->num_rows > 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($movie['title']); ?> - BoxFlix</title>
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
            max-width: 1200px;
            margin-left: auto;
            margin-right: auto;
        }
        .movie-container {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            animation: fadeIn 0.5s ease-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .video-container {
            position: relative;
            width: 100%;
            padding-top: 56.25%; /* 16:9 Aspect Ratio */
            background: #000;
        }
        .video-container video {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .movie-info {
            padding: 2rem;
        }
        .movie-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1.5rem;
        }
        .movie-title {
            font-size: 2rem;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 0.5rem;
        }
        .movie-meta {
            display: flex;
            gap: 1rem;
            color: #94a3b8;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }
        .movie-description {
            color: #e2e8f0;
            line-height: 1.6;
            margin-bottom: 2rem;
        }
        .movie-actions {
            display: flex;
            gap: 1rem;
        }
        .action-btn {
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .back-btn {
            background: rgba(255, 255, 255, 0.1);
            color: #e2e8f0;
        }
        .back-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }
        .wishlist-btn {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
        }
        .wishlist-btn:hover {
            background: rgba(239, 68, 68, 0.2);
            transform: translateY(-2px);
        }
        .wishlist-btn.active {
            background: rgba(239, 68, 68, 0.8);
            color: white;
        }
        .wishlist-btn i {
            transition: all 0.3s ease;
        }
        .wishlist-btn.active i {
            animation: heartBeat 0.3s ease-in-out;
        }
        @keyframes heartBeat {
            0% { transform: scale(1); }
            50% { transform: scale(1.3); }
            100% { transform: scale(1); }
        }
        .premium-badge {
            background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.9rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.3rem;
            box-shadow: 0 4px 15px rgba(251, 191, 36, 0.3);
        }
        .subscription-required {
            background: rgba(255, 255, 255, 0.05);
            padding: 2rem;
            border-radius: 15px;
            text-align: center;
            margin-top: 2rem;
        }
        .subscription-required h3 {
            font-size: 1.5rem;
            color: #ffffff;
            margin-bottom: 1rem;
        }
        .subscription-required p {
            color: #94a3b8;
            margin-bottom: 1.5rem;
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
        }
        .subscribe-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(96, 165, 250, 0.3);
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
        <div class="movie-container">
            <?php if ($movie['is_premium'] && !$has_subscription): ?>
                <div class="subscription-required">
                    <h3>Premium Content</h3>
                    <p>Subscribe to watch this premium movie and get access to all premium content.</p>
                    <a href="subscription.php" class="subscribe-btn">
                        <i class="fas fa-crown"></i>
                        Subscribe Now
                    </a>
                </div>
            <?php else: ?>
                <div class="video-container">
                    <video controls>
                        <source src="<?php echo htmlspecialchars($movie['video_path']); ?>" type="video/mp4">
                        Your browser does not support the video tag.
                    </video>
                </div>
            <?php endif; ?>
            
            <div class="movie-info">
                <div class="movie-header">
                    <div>
                        <h1 class="movie-title"><?php echo htmlspecialchars($movie['title']); ?></h1>
                        <div class="movie-meta">
                            <span><i class="fas fa-calendar"></i> <?php echo date('d M Y', strtotime($movie['created_at'])); ?></span>
                            <?php if ($movie['is_premium']): ?>
                                <span class="premium-badge">
                                    <i class="fas fa-crown"></i>
                                    Premium
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <p class="movie-description"><?php echo htmlspecialchars($movie['description']); ?></p>
                
                <div class="movie-actions">
                    <a href="index.php" class="action-btn back-btn">
                        <i class="fas fa-arrow-left"></i>
                        Back to Movies
                    </a>
                    <button class="action-btn wishlist-btn <?php echo $in_wishlist ? 'active' : ''; ?>" onclick="toggleWishlist(this)" data-movie-id="<?php echo $movie['id']; ?>">
                        <i class="<?php echo $in_wishlist ? 'fas' : 'far'; ?> fa-heart"></i>
                        <?php echo $in_wishlist ? 'Remove from Wishlist' : 'Add to Wishlist'; ?>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Function to toggle wishlist
        async function toggleWishlist(button) {
            const movieId = button.getAttribute('data-movie-id');
            const icon = button.querySelector('i');
            
            try {
                const response = await fetch('toggle_wishlist.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `movie_id=${movieId}`
                });
                
                const data = await response.json();
                
                if (data.success) {
                    if (data.action === 'added') {
                        button.classList.add('active');
                        icon.classList.remove('far');
                        icon.classList.add('fas');
                        button.textContent = 'Remove from Wishlist';
                        icon.style.animation = 'none';
                        icon.offsetHeight; // Trigger reflow
                        icon.style.animation = 'heartBeat 0.3s ease-in-out';
                    } else {
                        button.classList.remove('active');
                        icon.classList.remove('fas');
                        icon.classList.add('far');
                        button.textContent = 'Add to Wishlist';
                    }
                } else {
                    console.error('Error:', data.message);
                }
            } catch (error) {
                console.error('Error toggling wishlist:', error);
            }
        }
    </script>
</body>
</html> 