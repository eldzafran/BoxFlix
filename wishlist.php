<?php
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Check user's subscription status
$stmt = $conn->prepare("SELECT * FROM subscriptions WHERE user_id = ? AND is_active = 1 AND end_date > NOW()");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$subscription = $stmt->get_result()->fetch_assoc();
$has_subscription = $subscription !== null;

// Get user's wishlist movies
$stmt = $conn->prepare("
    SELECT f.* 
    FROM films f 
    JOIN wishlist w ON f.id = w.film_id 
    WHERE w.user_id = ? 
    ORDER BY w.created_at DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$wishlist_movies = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Wishlist - BoxFlix</title>
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
        .movies-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 2rem;
            padding: 1rem;
        }
        .movie-card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 15px;
            overflow: hidden;
            transition: all 0.3s ease;
            position: relative;
        }
        .movie-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }
        .movie-poster {
            width: 100%;
            height: 350px;
            object-fit: cover;
            transition: all 0.3s ease;
        }
        .movie-card:hover .movie-poster {
            transform: scale(1.05);
        }
        .movie-info {
            padding: 1.5rem;
            position: relative;
        }
        .movie-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #ffffff;
        }
        .movie-description {
            font-size: 0.9rem;
            color: #94a3b8;
            margin-bottom: 1rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .movie-actions {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }
        .watch-btn, .remove-btn {
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
        .watch-btn {
            background: linear-gradient(135deg, #60a5fa 0%, #3b82f6 100%);
            color: white;
            flex: 1;
            text-decoration: none;
        }
        .watch-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(96, 165, 250, 0.3);
        }
        .remove-btn {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
            padding: 0.8rem;
        }
        .remove-btn:hover {
            background: rgba(239, 68, 68, 0.2);
            transform: translateY(-2px);
        }
        .premium-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.3rem;
            box-shadow: 0 4px 15px rgba(251, 191, 36, 0.3);
        }
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 15px;
            margin: 2rem 0;
        }
        .empty-state i {
            font-size: 4rem;
            color: #60a5fa;
            margin-bottom: 1rem;
        }
        .empty-state h2 {
            font-size: 1.8rem;
            color: #ffffff;
            margin-bottom: 1rem;
        }
        .empty-state p {
            color: #94a3b8;
            margin-bottom: 2rem;
        }
        .browse-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.8rem 1.5rem;
            background: linear-gradient(135deg, #60a5fa 0%, #3b82f6 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .browse-btn:hover {
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
        <h1 class="page-title">
            <i class="fas fa-heart"></i>
            My Wishlist
        </h1>

        <?php if ($wishlist_movies->num_rows > 0): ?>
            <div class="movies-grid">
                <?php while ($movie = $wishlist_movies->fetch_assoc()): ?>
                    <div class="movie-card">
                        <img src="<?php echo htmlspecialchars($movie['poster_path']); ?>" alt="<?php echo htmlspecialchars($movie['title']); ?>" class="movie-poster">
                        <?php if ($movie['is_premium']): ?>
                            <div class="premium-badge">
                                <i class="fas fa-crown"></i>
                                Premium
                            </div>
                        <?php endif; ?>
                        <div class="movie-info">
                            <h3 class="movie-title"><?php echo htmlspecialchars($movie['title']); ?></h3>
                            <p class="movie-description"><?php echo htmlspecialchars($movie['description']); ?></p>
                            <div class="movie-actions">
                                <?php if ($movie['is_premium'] && !$has_subscription): ?>
                                    <a href="subscription.php" class="watch-btn">
                                        <i class="fas fa-crown"></i>
                                        Subscribe to Watch
                                    </a>
                                <?php else: ?>
                                    <a href="watch.php?id=<?php echo $movie['id']; ?>" class="watch-btn">
                                        <i class="fas fa-play"></i>
                                        Watch Now
                                    </a>
                                <?php endif; ?>
                                <button class="remove-btn" onclick="removeFromWishlist(<?php echo $movie['id']; ?>, this)">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-heart-broken"></i>
                <h2>Your wishlist is empty</h2>
                <p>Start adding movies to your wishlist to watch them later</p>
                <a href="index.php" class="browse-btn">
                    <i class="fas fa-film"></i>
                    Browse Movies
                </a>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function removeFromWishlist(movieId, button) {
            fetch('toggle_wishlist.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'movie_id=' + movieId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const card = button.closest('.movie-card');
                    card.style.opacity = '0';
                    card.style.transform = 'translateY(20px)';
                    setTimeout(() => {
                        card.remove();
                        // Check if there are any movies left
                        if (document.querySelectorAll('.movie-card').length === 0) {
                            location.reload();
                        }
                    }, 300);
                }
            })
            .catch(error => console.error('Error:', error));
        }

        // Add loading animation for movie cards
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        });

        document.querySelectorAll('.movie-card').forEach(card => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            card.style.transition = 'all 0.5s ease';
            observer.observe(card);
        });
    </script>
</body>
</html> 