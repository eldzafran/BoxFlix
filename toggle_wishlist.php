<?php
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit();
}

// Check if movie_id is provided
if (!isset($_POST['movie_id'])) {
    echo json_encode(['success' => false, 'message' => 'Movie ID is required']);
    exit();
}

$user_id = $_SESSION['user_id'];
$movie_id = $_POST['movie_id'];
$check_status = isset($_POST['check_status']) && $_POST['check_status'] == 1;

// Check if movie exists
$stmt = $conn->prepare("SELECT id FROM films WHERE id = ?");
$stmt->bind_param("i", $movie_id);
$stmt->execute();
if (!$stmt->get_result()->fetch_assoc()) {
    echo json_encode(['success' => false, 'message' => 'Movie not found']);
    exit();
}

// If just checking status
if ($check_status) {
    $stmt = $conn->prepare("SELECT id FROM wishlist WHERE user_id = ? AND film_id = ?");
    $stmt->bind_param("ii", $user_id, $movie_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    echo json_encode([
        'success' => true,
        'in_wishlist' => $result->num_rows > 0
    ]);
    exit();
}

// Toggle wishlist
$stmt = $conn->prepare("SELECT id FROM wishlist WHERE user_id = ? AND film_id = ?");
$stmt->bind_param("ii", $user_id, $movie_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Remove from wishlist
    $stmt = $conn->prepare("DELETE FROM wishlist WHERE user_id = ? AND film_id = ?");
    $stmt->bind_param("ii", $user_id, $movie_id);
    $stmt->execute();
    
    echo json_encode([
        'success' => true,
        'action' => 'removed',
        'message' => 'Movie removed from wishlist'
    ]);
} else {
    // Add to wishlist
    $stmt = $conn->prepare("INSERT INTO wishlist (user_id, film_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $user_id, $movie_id);
    $stmt->execute();
    
    echo json_encode([
        'success' => true,
        'action' => 'added',
        'message' => 'Movie added to wishlist'
    ]);
}
?> 