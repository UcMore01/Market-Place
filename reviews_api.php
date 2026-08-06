<?php
require_once 'db_conn.php';
require_once 'session.php';
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $product_id = intval($_GET['product_id'] ?? 0);
    if (!$product_id) {
        echo json_encode(['success' => true, 'reviews' => []]);
        exit;
    }
    $stmt = $pdo->prepare("
        SELECT r.id, r.rating, r.comment, r.created_at, r.helpful_count,
               u.username, u.user_id
        FROM reviews r
        JOIN users u ON r.user_id = u.user_id
        WHERE r.product_id = ?
        ORDER BY r.helpful_count DESC, r.created_at DESC
    ");
    $stmt->execute([$product_id]);
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $avgStmt = $pdo->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as total FROM reviews WHERE product_id = ?");
    $avgStmt->execute([$product_id]);
    $avg = $avgStmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'reviews' => $reviews, 'average_rating' => round($avg['avg_rating'], 1), 'total_reviews' => (int)$avg['total']]);
    exit;
}

if ($method === 'POST') {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Please log in to review.']);
        exit;
    }

    require_once 'csrf_helper.php';
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    $requestData = [];
    if (strpos($contentType, 'application/json') !== false) {
        $raw = file_get_contents('php://input');
        $requestData = json_decode($raw, true);
        if (!is_array($requestData)) {
            $requestData = [];
        }
    } else {
        $requestData = $_POST;
    }

    $action = $requestData['action'] ?? 'create';

    if ($action === 'helpful') {
        $review_id = intval($requestData['review_id'] ?? 0);
        if (!$review_id) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Review ID required.']);
            exit;
        }
        $stmt = $pdo->prepare("UPDATE reviews SET helpful_count = helpful_count + 1 WHERE id = ?");
        $stmt->execute([$review_id]);
        echo json_encode(['status' => 'success', 'message' => 'Vote recorded.']);
        exit;
    }

    $token = $requestData['csrf_token'] ?? null;
    if ($token === null) {
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
    }
    require_csrf_token($token);

    $product_id = intval($requestData['product_id'] ?? 0);
    $rating = intval($requestData['rating'] ?? 0);
    $comment = trim($requestData['comment'] ?? '');

    if (!$product_id || $rating < 1 || $rating > 5) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid review data.']);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO reviews (product_id, user_id, rating, comment) VALUES (?, ?, ?, ?)");
    $success = $stmt->execute([$product_id, $_SESSION['user_id'], $rating, $comment ?: null]);

    if ($success) {
        echo json_encode(['status' => 'success', 'message' => 'Review submitted.']);
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Failed to submit review.']);
    }
    exit;
}

if ($method === 'PUT') {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Please log in.']);
        exit;
    }

    parse_str(file_get_contents('php://input'), $putData);
    $review_id = intval($putData['review_id'] ?? 0);
    $rating = intval($putData['rating'] ?? 0);
    $comment = trim($putData['comment'] ?? '');

    if (!$review_id || ($rating < 1 || $rating > 5)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid review data.']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT r.user_id FROM reviews r WHERE r.id = ?");
    $stmt->execute([$review_id]);
    $review = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$review) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Review not found.']);
        exit;
    }

    if ($review['user_id'] != $_SESSION['user_id']) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'You can only edit your own reviews.']);
        exit;
    }

    $updateStmt = $pdo->prepare("UPDATE reviews SET rating = ?, comment = ? WHERE id = ?");
    $success = $updateStmt->execute([$rating, $comment ?: null, $review_id]);

    if ($success) {
        echo json_encode(['status' => 'success', 'message' => 'Review updated.']);
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Failed to update review.']);
    }
    exit;
}

if ($method === 'DELETE') {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Please log in.']);
        exit;
    }

    parse_str(file_get_contents('php://input'), $delData);
    $review_id = intval($delData['review_id'] ?? 0);

    if (!$review_id) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Review ID required.']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT r.user_id FROM reviews r WHERE r.id = ?");
    $stmt->execute([$review_id]);
    $review = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$review) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Review not found.']);
        exit;
    }

    if ($review['user_id'] != $_SESSION['user_id']) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'You can only delete your own reviews.']);
        exit;
    }

    $delStmt = $pdo->prepare("DELETE FROM reviews WHERE id = ?");
    $success = $delStmt->execute([$review_id]);

    if ($success) {
        echo json_encode(['status' => 'success', 'message' => 'Review deleted.']);
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Failed to delete review.']);
    }
    exit;
}

http_response_code(405);
echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);