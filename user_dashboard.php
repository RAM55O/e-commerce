<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?error=Please login first");
    exit();
}

if ($_SESSION['user_type'] === 'admin') {
    header("Location: admin_dashboard.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$user_stmt = $conn->prepare("SELECT username, email, first_name, surname FROM users WHERE id = ?");
$user_stmt->execute([$user_id]);
$current_user = $user_stmt->fetch(PDO::FETCH_ASSOC);
if (!$current_user) {
    header("Location: logout.php");
    exit();
}

$username = $current_user['username'];
$display_name = trim(($current_user['first_name'] ?? '') . ' ' . ($current_user['surname'] ?? ''));

$cart_stmt = $conn->prepare("SELECT SUM(quantity) as count FROM cart WHERE user_id = ?");
$cart_stmt->execute([$user_id]);
$cart_count = (int)($cart_stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0);

$wish_stmt = $conn->prepare("SELECT COUNT(*) as count FROM wishlist WHERE user_id = ?");
$wish_stmt->execute([$user_id]);
$wishlist_count = (int)($wish_stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0);

$order_stmt = $conn->prepare("SELECT COUNT(*) as count FROM orders WHERE user_id = ?");
$order_stmt->execute([$user_id]);
$order_count = (int)($order_stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Panel - Moonchild</title>
    <link rel="stylesheet" href="shop.css?v=3.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="page-header">
        <a href="home.php" class="back-btn"><i class="fas fa-arrow-left"></i></a>
        <h1 class="page-title">User Panel</h1>
    </div>

    <main class="main-content">
        <section class="section">
            <h3 class="section-title">Welcome, <?php echo htmlspecialchars($display_name ?: $username); ?></h3>
            <p style="color:#94a3b8;margin-bottom:20px;">Shop, compare mobiles, track orders, and apply offers from here.</p>
            <div class="user-feature-grid">
                <a href="compare.php" class="user-feature-card">
                    <i class="fas fa-balance-scale"></i>
                    <h4>Mobile comparison</h4>
                    <p>Compare up to 3 phones side by side</p>
                </a>
                <a href="cart.php" class="user-feature-card">
                    <i class="fas fa-shopping-cart"></i>
                    <h4>Cart</h4>
                    <p><?php echo $cart_count; ?> item(s) in your cart</p>
                </a>
                <a href="wishlist.php" class="user-feature-card">
                    <i class="fas fa-heart"></i>
                    <h4>Wishlist</h4>
                    <p><?php echo $wishlist_count; ?> saved item(s)</p>
                </a>
                <a href="search.php" class="user-feature-card">
                    <i class="fas fa-search"></i>
                    <h4>Search & Filter</h4>
                    <p>Find products by brand, price, rating</p>
                </a>
                <a href="orders.php" class="user-feature-card">
                    <i class="fas fa-box"></i>
                    <h4>Orders</h4>
                    <p><?php echo $order_count; ?> order(s) placed</p>
                </a>
                <a href="checkout.php" class="user-feature-card">
                    <i class="fas fa-credit-card"></i>
                    <h4>Payment</h4>
                    <p>COD, Card, or UPI checkout</p>
                </a>
                <a href="offers.php" class="user-feature-card">
                    <i class="fas fa-percent"></i>
                    <h4>Discounts & offers</h4>
                    <p>Deals plus coupon codes WELCOME10, SAVE20, MOBILE15</p>
                </a>
                <a href="profile.php" class="user-feature-card">
                    <i class="fas fa-user"></i>
                    <h4>Profile</h4>
                    <p>Update address and contact details</p>
                </a>
            </div>
        </section>
    </main>

    <?php $nav_active = 'profile'; include 'includes/user_nav.php'; ?>
    <script src="script.js?v=2.1"></script>
</body>
</html>
