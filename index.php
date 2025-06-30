<?php
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Get user's subscription status
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM subscriptions WHERE user_id = ? AND is_active = 1 AND end_date > NOW()");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$subscription = $stmt->get_result()->fetch_assoc();
$has_subscription = $subscription !== null;

// Get free movies
$free_movies = $conn->query("SELECT * FROM films WHERE is_premium = 0 ORDER BY created_at DESC");

// Get premium movies
$premium_movies = $conn->query("SELECT * FROM films WHERE is_premium = 1 ORDER BY created_at DESC");

// Get user's wishlist
$wishlist = $conn->prepare("SELECT f.* FROM films f JOIN wishlist w ON f.id = w.film_id WHERE w.user_id = ?");
$wishlist->bind_param("i", $user_id);
$wishlist->execute();
$wishlist_movies = $wishlist->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BoxFlix - Watch Movies Online</title>
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
        .hero-section {
            background: linear-gradient(rgba(15, 23, 42, 0.8), rgba(15, 23, 42, 0.8)),
                        url('assets/images/hero-bg.jpg') center/cover;
            padding: 4rem 2rem;
            text-align: center;
            border-radius: 20px;
            margin-bottom: 3rem;
            position: relative;
            overflow: hidden;
        }
        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, rgba(96, 165, 250, 0.2), transparent);
            animation: shimmer 3s infinite;
        }
        @keyframes shimmer {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }
        .hero-title {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: #ffffff;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }
        .hero-subtitle {
            font-size: 1.2rem;
            color: #e2e8f0;
            margin-bottom: 2rem;
        }
        .search-container {
            max-width: 600px;
            margin: 0 auto;
            position: relative;
        }
        .search-input {
            width: 100%;
            padding: 1rem 1.5rem;
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 50px;
            background: rgba(255, 255, 255, 0.1);
            color: #ffffff;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        .search-input:focus {
            outline: none;
            border-color: #60a5fa;
            background: rgba(255, 255, 255, 0.15);
        }
        .search-input::placeholder {
            color: rgba(255, 255, 255, 0.6);
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
        .watch-btn, .wishlist-btn {
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
        }
        .watch-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(96, 165, 250, 0.3);
        }
        .wishlist-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(0, 0, 0, 0.5);
            border: none;
            border-radius: 50%;
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            z-index: 2;
        }
        .wishlist-btn i {
            color: #fff;
            font-size: 1.2rem;
            transition: all 0.3s ease;
        }
        .wishlist-btn:hover {
            background: rgba(0, 0, 0, 0.7);
            transform: scale(1.1);
        }
        .wishlist-btn.active {
            background: rgba(239, 68, 68, 0.8);
        }
        .wishlist-btn.active i {
            color: #fff;
            animation: heartBeat 0.3s ease-in-out;
        }
        @keyframes heartBeat {
            0% { transform: scale(1); }
            50% { transform: scale(1.3); }
            100% { transform: scale(1); }
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
        .section-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 2rem;
            color: #ffffff;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .section-title i {
            color: #60a5fa;
        }
        .category-tabs {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            overflow-x: auto;
            padding-bottom: 1rem;
        }
        .category-tab {
            padding: 0.8rem 1.5rem;
            background: rgba(255, 255, 255, 0.1);
            border: none;
            border-radius: 50px;
            color: #e2e8f0;
            cursor: pointer;
            transition: all 0.3s ease;
            white-space: nowrap;
        }
        .category-tab:hover, .category-tab.active {
            background: #60a5fa;
            color: white;
            transform: translateY(-2px);
        }
        .error-message {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            animation: shake 0.5s ease-in-out;
        }
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
        .success-message {
            background: rgba(34, 197, 94, 0.1);
            color: #22c55e;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            animation: slideIn 0.5s ease-out;
        }
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
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
        <div class="hero-section">
            <h1 class="hero-title">Welcome to BoxFlix</h1>
            <p class="hero-subtitle">Unbox Your Film Anywhere & Anytime</p>
            <div class="search-container">
                <input type="text" class="search-input" placeholder="Search for movies..." id="searchInput">
            </div>
        </div>

        <h2 class="section-title">
            <i class="fas fa-film"></i>
            Standard Movies
        </h2>

        <div class="movies-grid">
            <?php while ($movie = $free_movies->fetch_assoc()): ?>
                <div class="movie-card">
                    <img src="<?php echo htmlspecialchars($movie['poster_path']); ?>" alt="<?php echo htmlspecialchars($movie['title']); ?>" class="movie-poster">
                    <div class="movie-info">
                        <h3 class="movie-title"><?php echo htmlspecialchars($movie['title']); ?></h3>
                        <p class="movie-description"><?php echo htmlspecialchars($movie['description']); ?></p>
                        <div class="movie-actions">
                            <a href="watch.php?id=<?php echo $movie['id']; ?>" class="watch-btn">
                                <i class="fas fa-play"></i>
                                Watch Now
                            </a>
                            <button class="wishlist-btn" onclick="toggleWishlist(this)" data-movie-id="<?php echo $movie['id']; ?>">
                                <i class="far fa-heart"></i>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>

        <h2 class="section-title" style="margin-top: 3rem;">
            <i class="fas fa-crown"></i>
            Premium Movies
        </h2>

        <div class="movies-grid">
            <?php while ($movie = $premium_movies->fetch_assoc()): ?>
                <div class="movie-card">
                    <img src="<?php echo htmlspecialchars($movie['poster_path']); ?>" alt="<?php echo htmlspecialchars($movie['title']); ?>" class="movie-poster">
                    <div class="premium-badge">
                        <i class="fas fa-crown"></i>
                        Premium
                    </div>
                    <div class="movie-info">
                        <h3 class="movie-title"><?php echo htmlspecialchars($movie['title']); ?></h3>
                        <p class="movie-description"><?php echo htmlspecialchars($movie['description']); ?></p>
                        <div class="movie-actions">
                            <?php if ($has_subscription): ?>
                                <a href="watch.php?id=<?php echo $movie['id']; ?>" class="watch-btn">
                                    <i class="fas fa-play"></i>
                                    Watch Now
                                </a>
                            <?php else: ?>
                                <a href="subscription.php" class="watch-btn">
                                    <i class="fas fa-crown"></i>
                                    Subscribe to Watch
                                </a>
                            <?php endif; ?>
                            <button class="wishlist-btn" onclick="toggleWishlist(this)" data-movie-id="<?php echo $movie['id']; ?>">
                                <i class="far fa-heart"></i>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>

    <script>
        // Search functionality
        const searchInput = document.getElementById('searchInput');
        const movieCards = document.querySelectorAll('.movie-card');

        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            
            movieCards.forEach(card => {
                const title = card.querySelector('.movie-title').textContent.toLowerCase();
                const description = card.querySelector('.movie-description').textContent.toLowerCase();
                
                if (title.includes(searchTerm) || description.includes(searchTerm)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });

        // Function to check wishlist status
        async function checkWishlistStatus() {
            const wishlistButtons = document.querySelectorAll('.wishlist-btn');
            
            for (const button of wishlistButtons) {
                const movieId = button.getAttribute('data-movie-id');
                try {
                    const response = await fetch('toggle_wishlist.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `movie_id=${movieId}&check_status=1`
                    });
                    
                    const data = await response.json();
                    if (data.in_wishlist) {
                        button.classList.add('active');
                        button.querySelector('i').classList.remove('far');
                        button.querySelector('i').classList.add('fas');
                    } else {
                        button.classList.remove('active');
                        button.querySelector('i').classList.remove('fas');
                        button.querySelector('i').classList.add('far');
                    }
                } catch (error) {
                    console.error('Error checking wishlist status:', error);
                }
            }
        }

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
                        icon.style.animation = 'none';
                        icon.offsetHeight; // Trigger reflow
                        icon.style.animation = 'heartBeat 0.3s ease-in-out';
                    } else {
                        button.classList.remove('active');
                        icon.classList.remove('fas');
                        icon.classList.add('far');
                    }
                } else {
                    console.error('Error:', data.message);
                }
            } catch (error) {
                console.error('Error toggling wishlist:', error);
            }
        }

        // Check wishlist status when page loads
        document.addEventListener('DOMContentLoaded', checkWishlistStatus);

        // Add smooth scrolling
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });

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