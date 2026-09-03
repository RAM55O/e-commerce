<?php
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?error=Please login first");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];

// Get product ID
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$product_id) {
    header("Location: home.php");
    exit();
}

// Get product details
$stmt = $conn->prepare("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    header("Location: home.php");
    exit();
}

// Parse specifications
$specs = [];
if ($product['specifications']) {
    $specs = explode('|', $product['specifications']);
}

// Get cart count
$cart_stmt = $conn->prepare("SELECT SUM(quantity) as count FROM cart WHERE user_id = ?");
$cart_stmt->execute([$user_id]);
$cart_count = $cart_stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;

$wishlist_ids = user_wishlist_ids($conn, (int)$user_id);
$in_wishlist = in_array((int)$product['id'], $wishlist_ids, true);
$mobiles_category_id = get_mobiles_category_id($conn);
$is_mobile = $mobiles_category_id && (int)$product['category_id'] === $mobiles_category_id;
$sale = sale_price($product);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - Moonchild</title>
    <link rel="stylesheet" href="shop.css?v=3.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header-content">
            <a href="home.php" class="back-btn"><i class="fas fa-arrow-left"></i></a>
            <form class="search-bar" action="search.php" method="GET">
                <i class="fas fa-search"></i>
                <input type="text" name="q" placeholder="Search products...">
            </form>
            <div class="header-icons">
                <a href="wishlist.php" class="icon-btn">
                    <i class="fas fa-heart"></i>
                </a>
                <a href="cart.php" class="icon-btn">
                    <i class="fas fa-shopping-cart"></i>
                    <?php if($cart_count > 0): ?>
                    <span class="badge"><?php echo $cart_count; ?></span>
                    <?php endif; ?>
                </a>
            </div>
        </div>
    </header>

    <!-- Product Detail -->
    <main class="main-content">
        <div class="product-detail">
            <!-- Product Gallery -->
            <div class="product-gallery">
                <div class="main-image">
                    <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                </div>
                <div class="thumbnail-row">
                    <div class="thumbnail active">
                        <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="">
                    </div>
                </div>
            </div>

            <!-- Product Details -->
            <div class="product-details">
                <h1><?php echo htmlspecialchars($product['name']); ?></h1>
                
                <div class="product-rating">
                    <div class="stars">
                        <?php 
                        $rating = $product['rating'];
                        for($i = 1; $i <= 5; $i++) {
                            if ($i <= $rating) {
                                echo '<i class="fas fa-star"></i>';
                            } elseif ($i - 0.5 <= $rating) {
                                echo '<i class="fas fa-star-half-alt"></i>';
                            } else {
                                echo '<i class="far fa-star"></i>';
                            }
                        }
                        ?>
                    </div>
                    <span class="rating-text">(<?php echo $rating; ?> / 5.0)</span>
                    <span class="brand">Brand: <?php echo htmlspecialchars($product['brand']); ?></span>
                </div>

                <div class="detail-price">
                    <?php if ((int)$product['discount'] > 0): ?>
                    <span class="original-price">₹<?php echo number_format($product['price'], 2); ?></span>
                    ₹<?php echo number_format($sale, 2); ?>
                    <span class="discount-badge" style="position:static;margin-left:8px;">-<?php echo (int)$product['discount']; ?>%</span>
                    <?php else: ?>
                    ₹<?php echo number_format($product['price'], 2); ?>
                    <?php endif; ?>
                </div>

                <?php if($user_type === 'user'): ?>
                <form action="cart_action.php" method="POST">
                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="quantity-selector">
                        <span>Quantity:</span>
                        <div class="qty-controls">
                            <button type="button" class="qty-btn" onclick="changeQty(-1)">−</button>
                            <input type="number" name="quantity" class="qty-input" value="1" min="1" max="<?php echo $product['stock']; ?>" id="qty">
                            <button type="button" class="qty-btn" onclick="changeQty(1)">+</button>
                        </div>
                    </div>

                    <div class="action-buttons">
                        <button type="submit" class="btn-cart">
                            <i class="fas fa-cart-plus"></i> Add to Cart
                        </button>
                        <button type="submit" name="action" value="buy" class="btn-buy">Buy Now</button>
                    </div>
                </form>
                <div class="action-buttons" style="margin-top:10px;">
                    <form action="wishlist_action.php" method="POST">
                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                        <input type="hidden" name="action" value="<?php echo $in_wishlist ? 'remove' : 'add'; ?>">
                        <input type="hidden" name="redirect" value="product.php?id=<?php echo $product['id']; ?>">
                        <button type="submit" class="btn-cart" style="background:<?php echo $in_wishlist ? '#ff6b6b' : 'rgba(255,255,255,0.08)'; ?>">
                            <i class="<?php echo $in_wishlist ? 'fas' : 'far'; ?> fa-heart"></i>
                            <?php echo $in_wishlist ? 'In Wishlist' : 'Add to Wishlist'; ?>
                        </button>
                    </form>
                    <?php if ($is_mobile): ?>
                    <a href="compare.php?add=<?php echo $product['id']; ?>" class="btn-buy">
                        <i class="fas fa-balance-scale"></i> Compare
                    </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <!-- Accordion -->
                <div class="accordion">
                    <div class="accordion-item">
                        <button class="accordion-header" onclick="toggleAccordion(this)">
                            <span>Description</span>
                            <i class="fas fa-chevron-down"></i>
                        </button>
                        <div class="accordion-content active">
                            <?php echo htmlspecialchars($product['description']); ?>
                        </div>
                    </div>
                    
                    <div class="accordion-item">
                        <button class="accordion-header" onclick="toggleAccordion(this)">
                            <span>Specifications</span>
                            <i class="fas fa-chevron-down"></i>
                        </button>
                        <div class="accordion-content">
                            <ul style="padding-left: 20px;">
                                <?php foreach($specs as $spec): ?>
                                <li style="margin-bottom: 8px;"><?php echo htmlspecialchars($spec); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="accordion-item">
                        <button class="accordion-header" onclick="toggleAccordion(this)">
                            <span>Shipping & Returns</span>
                            <i class="fas fa-chevron-down"></i>
                        </button>
                        <div class="accordion-content">
                            <p>Free shipping on orders over $50. 30-day return policy for unused items in original packaging.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

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
        <a href="wishlist.php" class="nav-item">
            <i class="fas fa-heart"></i>
            <span>Wishlist</span>
        </a>
        <a href="profile.php" class="nav-item">
            <i class="fas fa-user"></i>
            <span>Profile</span>
        </a>
    </nav>

    <script>
        function changeQty(delta) {
            const input = document.getElementById('qty');
            let val = parseInt(input.value) + delta;
            if (val < 1) val = 1;
            if (val > <?php echo $product['stock']; ?>) val = <?php echo $product['stock']; ?>;
            input.value = val;
        }

        function toggleAccordion(btn) {
            const content = btn.nextElementSibling;
            content.classList.toggle('active');
            btn.querySelector('i').classList.toggle('fa-chevron-up');
        }
    </script>
    <script src="script.js?v=2.1"></script>
</body>
</html>
