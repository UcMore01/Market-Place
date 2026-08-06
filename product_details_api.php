<?php
session_start();
require_once "session.php";
require_once "db_conn.php"; // Connects to database

header("Content-Type: application/json");

require_once "csrf_helper.php";

$method = $_SERVER["REQUEST_METHOD"];

if ($method === "POST") {
    require_csrf_token();
}

if ($method === "GET" && isset($_GET["id"])) {
    fetchProductDetails($_GET["id"]);
} elseif ($method === "POST" && isset($_POST["action"])) {
    if (!isset($_SESSION["user_id"])) {
        echo json_encode(["status" => "error", "message" => "Unauthorized access. Please log in."]);
        exit();
    }

    $product_id = $_POST["product_id"];
    
    if ($_POST["action"] === "add_to_cart") {
        addToCart($product_id);
    } elseif ($_POST["action"] === "buy_now") {
        buyNow($product_id);
    } else {
        echo json_encode(["status" => "error", "message" => "Invalid action."]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Invalid request"]);
    exit();
}

// ✅ **Function to Fetch Product Details**
function fetchProductDetails($id)
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($product) {
        echo json_encode(["status" => "success", "product" => $product]);
    } else {
        echo json_encode(["status" => "error", "message" => "Product not found"]);
    }
}

// ✅ **Function to Add Product to Cart**
function addToCart($product_id)
{
    global $pdo;
    $user_id = $_SESSION["user_id"];

    // Check if product is already in cart
    $stmt = $pdo->prepare("SELECT * FROM cart WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$user_id, $product_id]);
    $existing = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($existing) > 0) {
        echo json_encode(["status" => "info", "message" => "Product already in cart"]);
    } else {
        // Add to cart
        $stmt = $pdo->prepare("INSERT INTO cart (user_id, product_id) VALUES (?, ?)");
        if ($stmt->execute([$user_id, $product_id])) {
            echo json_encode(["status" => "success", "message" => "Product added to cart"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Failed to add product to cart"]);
        }
    }
}

// ✅ **Function to Handle "Buy Now" (Add to Cart & Redirect to Checkout)**
function buyNow($product_id)
{
    addToCart($product_id);
    echo json_encode(["status" => "redirect", "url" => "checkout.html"]);
}
?>
