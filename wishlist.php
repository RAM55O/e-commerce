<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?error=Please login first");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];

// Get wishlist items
$stmt = $conn->prepare("SELECT w.*, p.name, p.price, p.image, p.discount FROM wishlist w JOIN products p ON w.product_id = p.id WHERE w.user_id = ?");
$stmt->execute([$user_id]);
$wishlist_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wishlist - Moonchild</title>
    <link rel="stylesheet" href="shop.css?v=2.1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Header -->
    <div class="page-header">
        <a href="home.php" class="back-btn"><i class="fas fa-arrow-left"></i></a>
        <h1 class="page-title">My Wishlist</h1>
    </div>

    <div class="cart-container">
        <?php if(isset($_GET['success'])): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
        <?php endif; ?>

        <?php if(count($wishlist_items) > 0): ?>
            <?php foreach($wishlist_items as $item): ?>
            <div class="cart-item">
                <div class="cart-item-image">
                    <a href="product.php?id=<?php echo $item['product_id']; ?>">
                        <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="">
                    </a>
                </div>
                <div class="cart-item-details">
                    <h4><?php echo htmlspecialchars($item['name']); ?></h4>
                    <p class="price">₹<?php echo number_format(sale_price($item), 2); ?></p>
                    <?php if($user_type === 'user'): ?>
                    <form action="wishlist_action.php" method="POST" style="margin-top: 10px;">
                        <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                        <input type="hidden" name="action" value="move_to_cart">
                        <button type="submit" class="btn-edit" style="padding: 8px 15px;">
                            <i class="fas fa-cart-plus"></i> Move to Cart
                        </button>
                    </form>
                    <?php endif; ?>
                </div>
                <div class="cart-item-actions">
                    <form action="wishlist_action.php" method="POST">
                        <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                        <input type="hidden" name="action" value="remove">
                        <button type="submit" class="remove-btn"><i class="fas fa-trash"></i></button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-cart">
                <i class="fas fa-heart"></i>
                <h3>Your wishlist is empty</h3>
                <p>Save items you love to your wishlist!</p>
                <a href="home.php" class="btn-primary" style="display: inline-block; margin-top: 20px;">Browse Products</a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Bottom Navigation -->
    <nav class="bottom-nav">
        <a href="home.php" class="nav-item">
            <i class="fas fa-home"></i>
            <span>Home</span>
        </a>
        <a href="offers.php" class="nav-item">
            <i class="fas fa-percent"></i>
            <span>Offers</span>
        </a>
        <a href="cart.php" class="nav-item center-btn">
            <div class="nav-icon-wrapper">
                <i class="fas fa-shopping-cart"></i>
            </div>
            <span>Cart</span>
        </a>
        <a href="wishlist.php" class="nav-item active">
            <i class="fas fa-heart"></i>
            <span>Wishlist</span>
        </a>
        <a href="profile.php" class="nav-item">
            <i class="fas fa-user"></i>
            <span>Profile</span>
        </a>
    </nav>
    <script src="script.js?v=2.1"></script>
</body>
</html>
