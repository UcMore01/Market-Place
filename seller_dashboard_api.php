<?php
require_once "db_conn.php"; // Sets up $pdo

if (session_status() == PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'seller') {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

header("Content-Type: application/json");
$seller_id = $_GET['seller_id'] ?? $_SESSION['user_id'] ?? null;

    if (!$seller_id) {
        echo json_encode(["success" => false, "message" => "Seller ID is required"]);
        exit;
    }

    try {
        // Sales stats
        $stats = [
            "total_sales" => 0,
            "total_orders" => 0,
            "pending_orders" => 0,
        ];

        // Total sales (sum of all completed orders for this seller)
        $stmt = $pdo->prepare("SELECT SUM(total_price) as total_sales FROM orders WHERE seller_id = ? AND status = 'completed'");
        $stmt->execute([$seller_id]);
        $stats["total_sales"] = floatval($stmt->fetchColumn() ?: 0);

        // Total orders
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE seller_id = ?");
        $stmt->execute([$seller_id]);
        $stats["total_orders"] = intval($stmt->fetchColumn() ?: 0);

        // Pending orders
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE seller_id = ? AND status = 'pending'");
        $stmt->execute([$seller_id]);
        $stats["pending_orders"] = intval($stmt->fetchColumn() ?: 0);

        // Fetch seller's products
        $productQuery = "SELECT * FROM products WHERE seller_id = ?";
        $stmt = $pdo->prepare($productQuery);
        $stmt->execute([$seller_id]);
        $products = [];

        while ($product = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Build details/specification->images structure from the products
            // table (no separate product_details table exists in this schema).
            $img = $product['image_url'] ?: ($product['image'] ?? null);
            $specifications = $img ? ["Image" => [$img]] : [];
            // Structure for frontend: details as JSON string of {specification: [img, img, ...], ...}
            $product['details'] = json_encode($specifications);
            $products[] = $product;
        }

        echo json_encode(["success" => true, "stats" => $stats, "products" => $products]);
    } catch (Exception $e) {
        error_log("Seller dashboard API error: " . $e->getMessage());
        echo json_encode(["success" => false, "message" => "Error loading dashboard data."]);
    }
?>
